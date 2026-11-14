<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sends plain-text email with PHP mail() or a built-in SMTP client
 * (SSL or STARTTLS, AUTH LOGIN). Failures are logged and return false.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body, string $replyTo = ''): bool
    {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::log('Not sent: recipient "' . $to . '" is not a valid email address (check notify email in Settings).');
            return false;
        }
        $fromEmail = (string) config('mail.from_email', 'no-reply@localhost');
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = 'no-reply@' . ((string) parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
        }
        $fromName = self::clean((string) config('mail.from_name', site_name()));
        $replyTo = filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : '';
        $subject = self::clean($subject);

        $headers = [
            'Date' => date('r'),
            'From' => self::encode($fromName) . ' <' . $fromEmail . '>',
            'Reply-To' => $replyTo !== '' ? $replyTo : $fromEmail,
            'Message-ID' => '<' . bin2hex(random_bytes(12)) . '@' . (substr(strrchr($fromEmail, '@') ?: '@localhost', 1)) . '>',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => 'base64',
            'X-Mailer' => 'NoidaRankers',
        ];
        $encodedBody = rtrim(chunk_split(base64_encode(str_replace("\n", "\r\n", str_replace("\r\n", "\n", $body))), 76, "\r\n"));

        try {
            if (config('mail.transport') === 'smtp') {
                return self::smtp($fromEmail, $to, self::encode($subject), $headers, $encodedBody);
            }
            $headerLines = [];
            foreach ($headers as $name => $value) {
                if ($name !== 'Date') {
                    $headerLines[] = $name . ': ' . $value;
                }
            }
            $ok = mail($to, self::encode($subject), $encodedBody, implode("\r\n", $headerLines), '-f' . $fromEmail);
            if (!$ok) {
                self::log('PHP mail() returned false for "' . $subject . '".');
            }
            return $ok;
        } catch (\Throwable $e) {
            self::log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    /** @param array<string, string> $headers */
    private static function smtp(string $from, string $to, string $subject, array $headers, string $body): bool
    {
        $cfg = (array) config('mail.smtp', []);
        $host = (string) ($cfg['host'] ?? '');
        $port = (int) ($cfg['port'] ?? 587);
        $encryption = strtolower((string) ($cfg['encryption'] ?? 'tls'));
        $timeout = (int) ($cfg['timeout'] ?? 15);
        if ($host === '') {
            throw new \RuntimeException('SMTP host is not configured.');
        }
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'SNI_enabled' => true]]);
        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($socket, $timeout);
        $hostname = (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');

        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO ' . $hostname, [250]);
            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                        $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                    }
                }
                if (!stream_socket_enable_crypto($socket, true, $method)) {
                    throw new \RuntimeException('STARTTLS negotiation failed.');
                }
                self::command($socket, 'EHLO ' . $hostname, [250]);
            }
            $user = (string) ($cfg['username'] ?? '');
            if ($user !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($user), [334]);
                self::command($socket, base64_encode((string) ($cfg['password'] ?? '')), [235]);
            }
            self::command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $message = '';
            foreach ($headers as $name => $value) {
                $message .= $name . ': ' . $value . "\r\n";
            }
            $message .= 'To: <' . $to . ">\r\nSubject: " . $subject . "\r\n\r\n" . $body . "\r\n";
            // Dot-stuffing (base64 lines never start with ".", but be safe).
            $message = (string) preg_replace('/^\./m', '..', $message);
            fwrite($socket, $message . ".\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
            return true;
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket @param array<int, int> $codes */
    private static function command($socket, string $command, array $codes): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $codes);
    }

    /** @param resource $socket @param array<int, int> $codes */
    private static function expect($socket, array $codes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
        return $response;
    }

    private static function clean(string $value): string
    {
        return trim((string) preg_replace('/[\r\n\t]+/', ' ', $value));
    }

    private static function encode(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private static function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        $file = STORAGE_PATH . '/logs/mail.log';
        if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log('[mail] ' . $message);
        }
    }
}
