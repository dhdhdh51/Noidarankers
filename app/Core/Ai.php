<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Provider-agnostic AI engine for the blog writer.
 *
 * Works with any OpenAI-compatible endpoint (OpenAI, OpenRouter, Groq, Together,
 * DeepSeek, local LM Studio, …) and with Anthropic's native API. Everything is
 * configured from Admin > AI Writer > Settings: base URL (link), API key, model.
 *
 * It only ever GENERATES content (saved as a draft blog post for human review).
 * It never edits the site's code or runs commands.
 */
final class Ai
{
    /** Current configuration, with secrets decrypted. @return array<string,mixed> */
    public static function config(): array
    {
        $apiKey = Crypto::decrypt(setting('ai_api_key'));
        return [
            'enabled' => setting('ai_enabled') === '1',
            'base_url' => rtrim(setting('ai_base_url'), '/'),
            'format' => setting('ai_format') === 'anthropic' ? 'anthropic' : 'openai',
            'api_key' => $apiKey,
            'model' => trim(setting('ai_model')),
            'temperature' => (float) (setting('ai_temperature') !== '' ? setting('ai_temperature') : '0.7'),
            'image_enabled' => setting('ai_image_enabled') === '1',
            'image_base_url' => rtrim(setting('ai_image_base_url') !== '' ? setting('ai_image_base_url') : setting('ai_base_url'), '/'),
            'image_key' => Crypto::decrypt(setting('ai_image_key') !== '' ? setting('ai_image_key') : setting('ai_api_key')),
            'image_model' => trim(setting('ai_image_model') !== '' ? setting('ai_image_model') : 'gpt-image-1'),
            'image_size' => setting('ai_image_size') !== '' ? setting('ai_image_size') : '1024x1024',
        ];
    }

    public static function configured(): bool
    {
        $c = self::config();
        return $c['base_url'] !== '' && $c['api_key'] !== '' && $c['model'] !== '';
    }

    public static function ready(): bool
    {
        return self::configured() && self::config()['enabled'];
    }

    // ---------------------------------------------------------------- transport

    /**
     * @param array<int,string> $headers
     * @return array{status:int, body:string, error:string}
     */
    private static function http(string $method, string $url, array $headers, ?string $body, int $timeout = 120): array
    {
        if (!function_exists('curl_init')) {
            return ['status' => 0, 'body' => '', 'error' => 'The cURL PHP extension is not available on this server.'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'NoidaRankers-AI/1.0',
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = $resp === false ? (string) curl_error($ch) : '';
        curl_close($ch);
        return ['status' => $status, 'body' => is_string($resp) ? $resp : '', 'error' => $error];
    }

    /**
     * One chat/completion call. Returns the assistant text.
     * @param array<int,array{role:string,content:string}> $messages
     * @throws \RuntimeException with an admin-safe message on failure
     */
    public static function chat(array $messages, int $maxTokens = 4000, ?float $temperature = null): string
    {
        $c = self::config();
        if ($c['base_url'] === '' || $c['api_key'] === '' || $c['model'] === '') {
            throw new \RuntimeException('AI is not configured. Add the API link, key and model in AI Writer → Settings.');
        }
        $temperature = $temperature ?? (float) $c['temperature'];

        if ($c['format'] === 'anthropic') {
            $system = '';
            $msgs = [];
            foreach ($messages as $m) {
                if ($m['role'] === 'system') {
                    $system = trim($system . "\n\n" . $m['content']);
                } else {
                    $msgs[] = ['role' => $m['role'] === 'assistant' ? 'assistant' : 'user', 'content' => $m['content']];
                }
            }
            $payload = ['model' => $c['model'], 'max_tokens' => $maxTokens, 'temperature' => $temperature, 'messages' => $msgs];
            if ($system !== '') {
                $payload['system'] = $system;
            }
            $res = self::http('POST', $c['base_url'] . '/v1/messages', [
                'Content-Type: application/json',
                'x-api-key: ' . $c['api_key'],
                'anthropic-version: 2023-06-01',
            ], (string) json_encode($payload));
            $data = self::decodeOrThrow($res);
            $text = '';
            foreach ($data['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $text .= $block['text'] ?? '';
                }
            }
            if ($text === '') {
                throw new \RuntimeException('The AI returned an empty response.');
            }
            return $text;
        }

        // OpenAI-compatible
        $payload = ['model' => $c['model'], 'messages' => $messages, 'temperature' => $temperature, 'max_tokens' => $maxTokens];
        $res = self::http('POST', $c['base_url'] . '/chat/completions', [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $c['api_key'],
        ], (string) json_encode($payload));
        $data = self::decodeOrThrow($res);
        $text = (string) ($data['choices'][0]['message']['content'] ?? '');
        if ($text === '') {
            throw new \RuntimeException('The AI returned an empty response.');
        }
        return $text;
    }

    /** @param array{status:int,body:string,error:string} $res @return array<string,mixed> */
    private static function decodeOrThrow(array $res): array
    {
        if ($res['error'] !== '') {
            throw new \RuntimeException('Could not reach the AI service: ' . $res['error']);
        }
        $data = json_decode($res['body'], true);
        if ($res['status'] < 200 || $res['status'] >= 300) {
            $msg = is_array($data) ? (string) ($data['error']['message'] ?? ($data['message'] ?? '')) : '';
            throw new \RuntimeException('AI service error (HTTP ' . $res['status'] . ')' . ($msg !== '' ? ': ' . $msg : '. Check the base URL, key and model.'));
        }
        if (!is_array($data)) {
            throw new \RuntimeException('The AI service returned an unreadable response.');
        }
        return $data;
    }

    /** Lightweight connectivity/credentials test. @return array{ok:bool,message:string} */
    public static function test(): array
    {
        try {
            $reply = self::chat([
                ['role' => 'system', 'content' => 'You are a connectivity test. Reply with the single word: OK'],
                ['role' => 'user', 'content' => 'Reply with OK.'],
            ], 20, 0.0);
            return ['ok' => true, 'message' => 'Connected. Model replied: ' . trim(mb_substr($reply, 0, 80))];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** Fetch the list of available model IDs from the provider. @return array<int,string> */
    public static function models(): array
    {
        $c = self::config();
        if ($c['base_url'] === '' || $c['api_key'] === '') {
            return [];
        }
        if ($c['format'] === 'anthropic') {
            $res = self::http('GET', $c['base_url'] . '/v1/models', ['x-api-key: ' . $c['api_key'], 'anthropic-version: 2023-06-01'], null, 30);
        } else {
            $res = self::http('GET', $c['base_url'] . '/models', ['Authorization: Bearer ' . $c['api_key']], null, 30);
        }
        $data = json_decode($res['body'], true);
        if (!is_array($data)) {
            return [];
        }
        $list = $data['data'] ?? $data['models'] ?? [];
        $ids = [];
        foreach (is_array($list) ? $list : [] as $m) {
            $id = is_array($m) ? (string) ($m['id'] ?? $m['name'] ?? '') : (string) $m;
            if ($id !== '') {
                $ids[] = $id;
            }
        }
        sort($ids);
        return array_values(array_unique($ids));
    }

    // ---------------------------------------------------------------- blog generation

    /**
     * Generate a full SEO blog post. Returns structured fields ready to save as a draft.
     * @param array<string,mixed> $input
     * @return array{title:string,slug:string,meta_title:string,meta_description:string,excerpt:string,content:string,image_prompt:string,image_alt:string,tags:array<int,string>}
     */
    public static function generateBlog(array $input): array
    {
        $topic = trim((string) ($input['topic'] ?? ''));
        $keywords = trim((string) ($input['keywords'] ?? ''));
        $sourceUrl = trim((string) ($input['url'] ?? ''));
        $tone = trim((string) ($input['tone'] ?? 'professional, helpful'));
        $words = (int) ($input['words'] ?? 900);
        $words = max(400, min(2200, $words));

        $research = '';
        if ($sourceUrl !== '') {
            $research = self::fetchUrl($sourceUrl);
        }
        if ($topic === '' && $research !== '') {
            $topic = 'Write an article based on the referenced source';
        }

        // Internal links: give the model real published posts and pages to link to.
        $internal = self::internalLinkTargets();
        $linkList = '';
        foreach ($internal as $t) {
            $linkList .= '- ' . $t['title'] . ' => ' . $t['url'] . "\n";
        }

        $siteName = site_name();
        $system = "You are an expert SEO content writer and editor for {$siteName}, an SEO agency based in Noida, India. "
            . "You write original, accurate, genuinely helpful articles for business owners. "
            . "You never copy text from sources: you learn from them and write in your own words. "
            . "You write British/Indian English, in a {$tone} tone. Output MUST be valid minified JSON only — no markdown, no commentary.";

        $user = "Write a complete, SEO-optimised blog post.\n\n";
        $user .= "TOPIC: " . ($topic !== '' ? $topic : '(derive a strong topic from the keywords/source)') . "\n";
        if ($keywords !== '') {
            $user .= "TARGET KEYWORDS (use naturally, do not stuff): {$keywords}\n";
        }
        if ($research !== '') {
            $user .= "\nSOURCE MATERIAL FOR RESEARCH (rewrite in your own words, do NOT copy, do not mention that you were given this):\n\"\"\"\n" . mb_substr($research, 0, 6000) . "\n\"\"\"\n";
        }
        $user .= "\nLENGTH: about {$words} words.\n";
        $user .= "FORMAT the body as clean HTML using only these tags: <p>, <h2>, <h3>, <ul>, <ol>, <li>, <strong>, <em>, <a>, <blockquote>, <table>, <thead>, <tbody>, <tr>, <th>, <td>, <figure>, <figcaption>. Do NOT include <h1>, <html>, <head>, <style> or <script>. Start the body with an engaging intro paragraph (no heading).\n";
        if ($linkList !== '') {
            $user .= "\nINTERNAL LINKS — naturally link 2 to 4 relevant phrases in the body to these existing pages using <a href=\"RELATIVE_URL\">anchor</a> (use the exact relative URLs given):\n{$linkList}";
        }
        $user .= "\nAlso suggest a photorealistic, text-free FEATURED IMAGE described for an image generator (no words/letters in the image).\n";
        $user .= "\nReturn JSON with EXACTLY these keys:\n"
            . '{"title":"H1 title, max 60 chars","meta_title":"SEO title, max 60 chars","meta_description":"compelling, max 155 chars","excerpt":"1-2 sentence summary, max 160 chars","slug":"url-slug-in-kebab-case","content":"the HTML body","image_prompt":"image description","image_alt":"descriptive alt text for the image","tags":["3-6","short","tags"]}';

        $raw = self::chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], 6000);

        $data = self::parseJson($raw);
        if ($data === null) {
            throw new \RuntimeException('The AI response could not be parsed. Try again, or pick a different model.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException('The AI did not return a title. Please try again.');
        }
        $content = HtmlSanitizer::clean((string) ($data['content'] ?? ''));
        if (trim(strip_tags($content)) === '') {
            throw new \RuntimeException('The AI did not return any article content. Please try again.');
        }
        // Guarantee internal linking: append a "Related reading" block if the body has no internal links.
        if (stripos($content, 'href="/') === false && $internal !== []) {
            $items = '';
            foreach (array_slice($internal, 0, 3) as $t) {
                $items .= '<li><a href="' . e($t['url']) . '">' . e($t['title']) . '</a></li>';
            }
            if ($items !== '') {
                $content .= '<h2>Related reading</h2><ul>' . $items . '</ul>';
            }
        }

        $tags = [];
        foreach ((array) ($data['tags'] ?? []) as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') {
                $tags[] = mb_substr($tag, 0, 40);
            }
        }

        return [
            'title' => mb_substr($title, 0, 160),
            'slug' => Str::slug((string) ($data['slug'] ?? $title)),
            'meta_title' => mb_substr(trim((string) ($data['meta_title'] ?? $title)), 0, 70),
            'meta_description' => mb_substr(trim((string) ($data['meta_description'] ?? ($data['excerpt'] ?? ''))), 0, 170),
            'excerpt' => mb_substr(trim((string) ($data['excerpt'] ?? '')), 0, 300),
            'content' => $content,
            'image_prompt' => trim((string) ($data['image_prompt'] ?? '')),
            'image_alt' => mb_substr(trim((string) ($data['image_alt'] ?? $title)), 0, 190),
            'tags' => array_slice($tags, 0, 6),
        ];
    }

    /**
     * Generate an image from a prompt and store it as a processed upload.
     * @return array{path:string,width:int,height:int}|null
     */
    public static function generateImage(string $prompt, string $name = 'blog-image'): ?array
    {
        $c = self::config();
        if (!$c['image_enabled'] || $prompt === '' || $c['image_base_url'] === '' || $c['image_key'] === '') {
            return null;
        }
        $payload = [
            'model' => $c['image_model'],
            'prompt' => mb_substr($prompt, 0, 1000),
            'size' => $c['image_size'],
            'n' => 1,
        ];
        // DALL·E 3 needs an explicit response_format; gpt-image-1 returns b64 by default.
        if (stripos($c['image_model'], 'dall-e') !== false) {
            $payload['response_format'] = 'b64_json';
        }
        $res = self::http('POST', $c['image_base_url'] . '/images/generations', [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $c['image_key'],
        ], (string) json_encode($payload), 180);
        if ($res['error'] !== '' || $res['status'] < 200 || $res['status'] >= 300) {
            error_log('[ai-image] HTTP ' . $res['status'] . ' ' . $res['error'] . ' ' . mb_substr($res['body'], 0, 300));
            return null;
        }
        $data = json_decode($res['body'], true);
        $item = is_array($data) ? ($data['data'][0] ?? null) : null;
        if (!is_array($item)) {
            return null;
        }
        $bytes = '';
        if (!empty($item['b64_json'])) {
            $bytes = (string) base64_decode((string) $item['b64_json'], true);
        } elseif (!empty($item['url'])) {
            $dl = self::http('GET', (string) $item['url'], [], null, 60);
            if ($dl['status'] >= 200 && $dl['status'] < 300) {
                $bytes = $dl['body'];
            }
        }
        if ($bytes === '') {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'aiimg');
        if ($tmp === false) {
            return null;
        }
        file_put_contents($tmp, $bytes);
        try {
            return ImageProcessor::process($tmp, $name);
        } catch (\Throwable $e) {
            error_log('[ai-image] process failed: ' . $e->getMessage());
            return null;
        } finally {
            @unlink($tmp);
        }
    }

    // ---------------------------------------------------------------- helpers

    /** Published posts + key pages to offer as internal-link targets. @return array<int,array{title:string,url:string}> */
    private static function internalLinkTargets(): array
    {
        $out = [];
        try {
            foreach (Database::all("SELECT title, slug FROM posts WHERE status = 'published' ORDER BY COALESCE(published_at, created_at) DESC LIMIT 20") as $r) {
                $out[] = ['title' => (string) $r['title'], 'url' => '/blog/' . (string) $r['slug']];
            }
            foreach (Database::all("SELECT title, slug FROM services WHERE status = 'published' ORDER BY id LIMIT 10") as $r) {
                $out[] = ['title' => (string) $r['title'] . ' service', 'url' => '/services/' . (string) $r['slug']];
            }
        } catch (\Throwable $e) {
            // tables may differ; internal links are best-effort
        }
        $out[] = ['title' => 'our SEO pricing', 'url' => '/pricing'];
        $out[] = ['title' => 'free SEO audit', 'url' => '/free-seo-audit'];
        return $out;
    }

    /** Fetch readable text from a URL for research context. */
    public static function fetchUrl(string $url): string
    {
        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }
        $res = self::http('GET', $url, ['Accept: text/html'], null, 20);
        if ($res['status'] < 200 || $res['status'] >= 300 || $res['body'] === '') {
            return '';
        }
        $html = $res['body'];
        $html = (string) preg_replace('#<(script|style|noscript|svg|head)[^>]*>.*?</\1>#is', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);
        return trim(mb_substr($text, 0, 8000));
    }

    /** Parse a JSON object from the model output, tolerating code fences and stray text. @return array<string,mixed>|null */
    private static function parseJson(string $raw): ?array
    {
        $raw = trim($raw);
        $raw = (string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $raw);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $data = json_decode(substr($raw, $start, $end - $start + 1), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return null;
    }
}
