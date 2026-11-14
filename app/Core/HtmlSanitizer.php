<?php
declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist HTML sanitiser for content written in the admin editor.
 * Removes scripts, event handlers, inline styles and unsafe URLs.
 */
final class HtmlSanitizer
{
    /** Allowed tags => allowed attributes. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'mark' => [], 'small' => [],
        'sub' => [], 'sup' => [], 'code' => [], 'pre' => [], 'blockquote' => [], 'cite' => [],
        'ul' => [], 'ol' => ['start'], 'li' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading', 'decoding'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'th' => ['scope', 'colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'], 'caption' => [],
        'span' => [], 'div' => [],
    ];

    /** Removed together with everything inside them. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select',
        'option', 'svg', 'math', 'template', 'noscript', 'link', 'meta', 'base', 'frame', 'frameset', 'applet', 'audio', 'video', 'canvas', 'title', 'head'];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"?><!DOCTYPE html><html><body><div id="nr-root">' . $html . '</div></body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('nr-root');
        if (!$root instanceof DOMElement) {
            return '';
        }
        self::walk($root, $doc);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        // Remove empty paragraphs left by the editor.
        $out = (string) preg_replace('#<p>(\s|&nbsp;|<br>)*</p>#i', '', $out);
        return trim($out);
    }

    private static function walk(DOMNode $node, DOMDocument $doc): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'h1') {
                $child = self::rename($child, 'h2', $doc);
                $tag = 'h2';
            }
            if (!array_key_exists($tag, self::ALLOWED)) {
                // Keep the text, drop the unknown wrapper.
                self::walk($child, $doc);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            self::cleanAttributes($child, self::ALLOWED[$tag]);
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
                continue;
            }
            self::walk($child, $doc);
        }
    }

    /** @param array<int, string> $allowed */
    private static function cleanAttributes(DOMElement $el, array $allowed): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }
            $value = trim($attr->value);
            if (in_array($name, ['href', 'src'], true) && !self::safeUrl($value, $name === 'src')) {
                $el->removeAttribute($attr->name);
                continue;
            }
            if (in_array($name, ['width', 'height', 'colspan', 'rowspan', 'start'], true) && !preg_match('/^\d{1,4}$/', $value)) {
                $el->removeAttribute($attr->name);
            }
            if ($name === 'id' && !preg_match('/^[a-z][a-z0-9-]{0,60}$/', $value)) {
                $el->removeAttribute($attr->name);
            }
        }
        $tag = strtolower($el->tagName);
        if ($tag === 'a') {
            if ($el->getAttribute('target') !== '') {
                $el->setAttribute('target', '_blank');
                $el->setAttribute('rel', 'noopener noreferrer');
            } elseif ($el->hasAttribute('rel') && !preg_match('/^(nofollow|noopener|noreferrer|sponsored|ugc)( (nofollow|noopener|noreferrer|sponsored|ugc))*$/', $el->getAttribute('rel'))) {
                $el->removeAttribute('rel');
            }
        }
        if ($tag === 'img') {
            $el->setAttribute('loading', 'lazy');
            $el->setAttribute('decoding', 'async');
            if (!$el->hasAttribute('alt')) {
                $el->setAttribute('alt', '');
            }
        }
    }

    private static function safeUrl(string $url, bool $image): bool
    {
        $url = (string) preg_replace('/[\x00-\x20]+/', '', $url);
        if ($url === '') {
            return false;
        }
        if (str_starts_with($url, '#') || (str_starts_with($url, '/') && !str_starts_with($url, '//'))) {
            return true;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return $image ? in_array($scheme, ['https', 'http'], true) : in_array($scheme, ['https', 'http', 'mailto', 'tel'], true);
    }

    private static function rename(DOMElement $el, string $newTag, DOMDocument $doc): DOMElement
    {
        $new = $doc->createElement($newTag);
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode?->replaceChild($new, $el);
        return $new;
    }
}
