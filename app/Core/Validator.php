<?php
declare(strict_types=1);

namespace App\Core;

/** Small fluent validator. The first error per field wins. */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, string> $data */
    public function __construct(private array $data)
    {
    }

    public function value(string $field): string
    {
        return (string) ($this->data[$field] ?? '');
    }

    public function required(string $field, string $message): self
    {
        if (trim($this->value($field)) === '') {
            $this->add($field, $message);
        }
        return $this;
    }

    public function length(string $field, int $min, int $max, string $label): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        $len = mb_strlen($value);
        if ($len < $min) {
            $this->add($field, sprintf('%s must be at least %d characters.', $label, $min));
        } elseif ($len > $max) {
            $this->add($field, sprintf('%s must be %d characters or fewer.', $label, $max));
        }
        return $this;
    }

    public function email(string $field, string $message = 'Please enter a valid email address, like name@example.com.'): self
    {
        $value = $this->value($field);
        if ($value !== '' && (!filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value) > 190)) {
            $this->add($field, $message);
        }
        return $this;
    }

    /** Indian and international numbers: 10 to 15 digits, optional +, spaces, dashes. */
    public function phone(string $field, string $message = 'Please enter a valid phone number, like +91 98XXX XXXXX.'): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        $digits = preg_replace('/\D/', '', $value);
        if (!preg_match('/^\+?[0-9 ()\-.]{8,22}$/', $value) || strlen((string) $digits) < 10 || strlen((string) $digits) > 15) {
            $this->add($field, $message);
        }
        return $this;
    }

    public function url(string $field, string $message = 'Please enter a valid website address, like example.com.'): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        $normalised = self::normaliseUrl($value);
        if ($normalised === null) {
            $this->add($field, $message);
        } else {
            $this->data[$field] = $normalised;
        }
        return $this;
    }

    /** @param array<int, string> $allowed */
    public function in(string $field, array $allowed, string $message): self
    {
        $value = $this->value($field);
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->add($field, $message);
        }
        return $this;
    }

    public function slug(string $field, string $message = 'Use lowercase letters, numbers and hyphens only.'): self
    {
        $value = $this->value($field);
        if ($value !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            $this->add($field, $message);
        }
        return $this;
    }

    public function integer(string $field, int $min, int $max, string $label): self
    {
        $value = $this->value($field);
        if ($value === '') {
            return $this;
        }
        if (!preg_match('/^-?\d+$/', $value) || (int) $value < $min || (int) $value > $max) {
            $this->add($field, sprintf('%s must be a whole number between %d and %d.', $label, $min, $max));
        }
        return $this;
    }

    public function add(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, string> Data after normalisation (e.g. URLs). */
    public function data(): array
    {
        return $this->data;
    }

    public static function normaliseUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 255 || preg_match('/\s/', $value)) {
            return null;
        }
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }
        $parts = parse_url($value);
        if ($parts === false || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }
        if (!preg_match('/^(?=.{4,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $parts['host'])) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }
}
