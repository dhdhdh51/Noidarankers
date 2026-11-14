<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lead Finder engine. Pulls local-business leads from Google Maps using the
 * official Google Places API (New) — Text Search. Returns name, address,
 * phone, website, rating and a Maps link for each place.
 *
 * Configured from Admin > Lead Finder > Settings (Google API key, encrypted).
 * This never scrapes Google Maps; it uses the sanctioned, billed API.
 */
final class MapsLeads
{
    private const ENDPOINT = 'https://places.googleapis.com/v1/places:searchText';
    private const FIELD_MASK = 'places.id,places.displayName,places.formattedAddress,'
        . 'places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,'
        . 'places.rating,places.userRatingCount,places.googleMapsUri,'
        . 'places.primaryTypeDisplayName,places.businessStatus,nextPageToken';

    /** Unsaved overrides for the current request (Test before Save). @var array<string,mixed>|null */
    private static ?array $override = null;

    /** @param array<string,mixed> $override */
    public static function useRuntimeConfig(array $override): void
    {
        self::$override = $override;
    }

    /** @return array{enabled:bool,api_key:string} */
    public static function config(): array
    {
        $cfg = [
            'enabled' => setting('maps_enabled') === '1',
            'api_key' => Crypto::decrypt(setting('maps_api_key')),
        ];
        if (self::$override !== null) {
            foreach (self::$override as $k => $v) {
                if ($v !== null && $v !== '') {
                    $cfg[$k] = $v;
                }
            }
        }
        return $cfg;
    }

    public static function configured(): bool
    {
        return self::config()['api_key'] !== '';
    }

    /**
     * Search Google Maps for businesses. Paginates up to $maxResults (hard cap 60).
     *
     * @return array<int,array{place_id:string,name:string,category:string,phone:string,website:string,address:string,rating:float|null,reviews:int,maps_url:string}>
     * @throws \RuntimeException with an admin-safe message
     */
    public static function search(string $query, string $region = 'IN', int $maxResults = 20, float $minRating = 0.0, string $rank = 'RELEVANCE'): array
    {
        $apiKey = self::config()['api_key'];
        if ($apiKey === '') {
            throw new \RuntimeException('Add your Google Places API key in Lead Finder → Settings first.');
        }
        $query = trim($query);
        if ($query === '') {
            throw new \RuntimeException('Enter what to search for, e.g. "dentists in Noida".');
        }
        $maxResults = max(1, min(60, $maxResults));

        $out = [];
        $seen = [];
        $pageToken = '';
        $guard = 0;
        do {
            $payload = ['textQuery' => $query, 'pageSize' => 20];
            $region = strtoupper(trim($region));
            if ($region !== '' && preg_match('/^[A-Z]{2}$/', $region)) {
                $payload['regionCode'] = $region;
            }
            if ($minRating > 0) {
                $payload['minRating'] = round($minRating * 2) / 2; // nearest 0.5
            }
            if ($rank === 'DISTANCE') {
                $payload['rankPreference'] = 'DISTANCE';
            }
            if ($pageToken !== '') {
                $payload['pageToken'] = $pageToken;
            }

            $res = self::http($apiKey, $payload);
            $data = self::decode($res);

            foreach ($data['places'] ?? [] as $place) {
                $id = (string) ($place['id'] ?? '');
                if ($id === '' || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $out[] = self::normalize($place);
                if (count($out) >= $maxResults) {
                    return $out;
                }
            }

            $pageToken = (string) ($data['nextPageToken'] ?? '');
            if ($pageToken !== '') {
                usleep(1200000); // give Google a moment before reusing the page token
            }
        } while ($pageToken !== '' && ++$guard < 3);

        return $out;
    }

    /** Lightweight connectivity/credentials test. @return array{ok:bool,message:string} */
    public static function test(): array
    {
        try {
            $r = self::search('coffee shop in Noida', 'IN', 1);
            return ['ok' => true, 'message' => 'Connected — Google Places is working (' . count($r) . ' sample result).'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** @param array<string,mixed> $place @return array{place_id:string,name:string,category:string,phone:string,website:string,address:string,rating:float|null,reviews:int,maps_url:string} */
    private static function normalize(array $place): array
    {
        return [
            'place_id' => (string) ($place['id'] ?? ''),
            'name' => (string) ($place['displayName']['text'] ?? ''),
            'category' => (string) ($place['primaryTypeDisplayName']['text'] ?? ''),
            'phone' => (string) ($place['internationalPhoneNumber'] ?? $place['nationalPhoneNumber'] ?? ''),
            'website' => (string) ($place['websiteUri'] ?? ''),
            'address' => (string) ($place['formattedAddress'] ?? ''),
            'rating' => isset($place['rating']) ? (float) $place['rating'] : null,
            'reviews' => (int) ($place['userRatingCount'] ?? 0),
            'maps_url' => (string) ($place['googleMapsUri'] ?? ''),
        ];
    }

    /** @param array<string,mixed> $payload @return array{status:int,body:string,error:string} */
    private static function http(string $apiKey, array $payload): array
    {
        if (!function_exists('curl_init')) {
            return ['status' => 0, 'body' => '', 'error' => 'The cURL PHP extension is not available on this server.'];
        }
        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $apiKey,
                'X-Goog-FieldMask: ' . self::FIELD_MASK,
            ],
            CURLOPT_POSTFIELDS => (string) json_encode($payload),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'NoidaRankers-LeadFinder/1.0',
        ]);
        $resp = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = $resp === false ? (string) curl_error($ch) : '';
        curl_close($ch);
        return ['status' => $status, 'body' => is_string($resp) ? $resp : '', 'error' => $error];
    }

    /** @param array{status:int,body:string,error:string} $res @return array<string,mixed> */
    private static function decode(array $res): array
    {
        if ($res['error'] !== '') {
            throw new \RuntimeException('Could not reach Google Places: ' . $res['error']);
        }
        $data = json_decode($res['body'], true);
        if ($res['status'] < 200 || $res['status'] >= 300) {
            $msg = is_array($data) ? (string) ($data['error']['message'] ?? '') : '';
            throw new \RuntimeException('Google Places error (HTTP ' . $res['status'] . ')'
                . ($msg !== '' ? ': ' . $msg : '. Check the API key, and that "Places API (New)" is enabled with billing on.'));
        }
        if (!is_array($data)) {
            throw new \RuntimeException('Google Places returned an unreadable response.');
        }
        return $data;
    }
}
