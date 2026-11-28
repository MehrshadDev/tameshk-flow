<?php

namespace App\Admin;

use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class GoogleSearchConsoleClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';
    private const TOKEN_AUDIENCE = 'https://oauth2.googleapis.com/token';

    public function isConfigured(): bool
    {
        return $this->siteUrl() !== ''
            && $this->clientEmail() !== ''
            && $this->privateKey() !== '';
    }

    public function siteUrl(): string
    {
        return trim((string) config('services.gsc.site_url', ''));
    }

    public function fetchSnapshot(int $days = 28): array
    {
        $days = max(1, min(365, $days));

        if (! $this->isConfigured()) {
            throw new RuntimeException('تنظیمات گوگل سرچ کنسول در فایل .env تکمیل نشده است.');
        }

        $token = $this->getAccessToken();
        $siteUrl = $this->siteUrl();
        $encodedSite = rawurlencode($siteUrl);
        $apiBase = rtrim((string) config('services.gsc.api_base_url', 'https://searchconsole.googleapis.com'), '/');
        $analyticsUrl = "$apiBase/webmasters/v3/sites/$encodedSite/searchAnalytics/query";
        $sitemapsUrl = "$apiBase/webmasters/v3/sites/$encodedSite/sitemaps";

        $endDate = Carbon::now('America/Los_Angeles');
        $startDate = $endDate->copy()->subDays($days - 1);
        $prevEndDate = $startDate->copy()->subDay();
        $prevStartDate = $prevEndDate->copy()->subDays($days - 1);

        $curStart = $startDate->format('Y-m-d');
        $curEnd = $endDate->format('Y-m-d');
        $prvStart = $prevStartDate->format('Y-m-d');
        $prvEnd = $prevEndDate->format('Y-m-d');

        $responses = Http::pool(fn (Pool $pool) => [
            'current_totals' => $pool->as('current_totals')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'previous_totals' => $pool->as('previous_totals')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $prvStart,
                'endDate' => $prvEnd,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'daily' => $pool->as('daily')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'dimensions' => ['date'],
                'rowLimit' => 400,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'current_queries' => $pool->as('current_queries')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'dimensions' => ['query'],
                'rowLimit' => 300,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'previous_queries' => $pool->as('previous_queries')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $prvStart,
                'endDate' => $prvEnd,
                'dimensions' => ['query'],
                'rowLimit' => 600,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'current_pages' => $pool->as('current_pages')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'dimensions' => ['page'],
                'rowLimit' => 250,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'previous_pages' => $pool->as('previous_pages')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $prvStart,
                'endDate' => $prvEnd,
                'dimensions' => ['page'],
                'rowLimit' => 500,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'query_pages' => $pool->as('query_pages')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'dimensions' => ['query', 'page'],
                'rowLimit' => 600,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'devices' => $pool->as('devices')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'dimensions' => ['device'],
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'countries' => $pool->as('countries')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'dimensions' => ['country'],
                'rowLimit' => 25,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'appearances' => $pool->as('appearances')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'dimensions' => ['searchAppearance'],
                'rowLimit' => 25,
                'type' => 'web',
                'dataState' => 'all',
            ]),
            'image_totals' => $pool->as('image_totals')->withToken($token)->acceptJson()->timeout(30)->post($analyticsUrl, [
                'startDate' => $curStart,
                'endDate' => $curEnd,
                'type' => 'image',
                'dataState' => 'all',
            ]),
            'sitemaps' => $pool->as('sitemaps')->withToken($token)->acceptJson()->timeout(25)->get($sitemapsUrl),
        ]);

        $primary = $responses['current_totals'] ?? null;

        if ($primary instanceof Throwable) {
            throw new RuntimeException('عدم امکان برقراری ارتباط با سرور Google Search Console: ' . $primary->getMessage());
        }

        if (! $primary instanceof Response || $primary->failed()) {
            throw new RuntimeException($this->describeApiError($primary));
        }

        return [
            'meta' => [
                'site_url' => $siteUrl,
                'days' => $days,
                'current_start' => $curStart,
                'current_end' => $curEnd,
                'previous_start' => $prvStart,
                'previous_end' => $prvEnd,
            ],
            'current_totals' => $this->extractRows($responses['current_totals'] ?? null)[0] ?? null,
            'previous_totals' => $this->extractRows($responses['previous_totals'] ?? null)[0] ?? null,
            'image_totals' => $this->extractRows($responses['image_totals'] ?? null)[0] ?? null,
            'daily' => $this->extractRows($responses['daily'] ?? null),
            'current_queries' => $this->extractRows($responses['current_queries'] ?? null),
            'previous_queries' => $this->extractRows($responses['previous_queries'] ?? null),
            'current_pages' => $this->extractRows($responses['current_pages'] ?? null),
            'previous_pages' => $this->extractRows($responses['previous_pages'] ?? null),
            'query_pages' => $this->extractRows($responses['query_pages'] ?? null),
            'devices' => $this->extractRows($responses['devices'] ?? null),
            'countries' => $this->extractRows($responses['countries'] ?? null),
            'appearances' => $this->extractRows($responses['appearances'] ?? null),
            'sitemaps' => $this->extractSitemaps($responses['sitemaps'] ?? null),
        ];
    }

    private function getAccessToken(): string
    {
        $email = $this->clientEmail();
        $privateKey = $this->privateKey();
        $cacheKey = 'gsc_sa_token_' . hash('sha256', $email . '|' . $privateKey);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $assertion = $this->buildSignedJwt($email, $privateKey);
        $oauthBase = rtrim((string) config('services.google.oauth_base_url', 'https://oauth2.googleapis.com'), '/');

        try {
            $response = Http::asForm()->timeout(20)->post("$oauthBase/token", [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException('خطا در دریافت توکن احراز هویت از گوگل: ' . $e->getMessage());
        }

        if ($response->failed()) {
            $desc = (string) (data_get($response->json(), 'error_description') ?? data_get($response->json(), 'error') ?? $response->body());
            throw new RuntimeException('احراز هویت Service Account گوگل ناموفق بود: ' . mb_substr(trim(strip_tags($desc)), 0, 250));
        }

        $accessToken = trim((string) data_get($response->json(), 'access_token', ''));

        if ($accessToken === '') {
            throw new RuntimeException('توکن دسترسی از سرور احراز هویت گوگل دریافت نشد.');
        }

        $expiresIn = max(120, (int) data_get($response->json(), 'expires_in', 3600) - 180);
        Cache::put($cacheKey, $accessToken, now()->addSeconds($expiresIn));

        return $accessToken;
    }

    private function buildSignedJwt(string $email, string $privateKey): string
    {
        if (! function_exists('openssl_sign')) {
            throw new RuntimeException('افزونه OpenSSL روی سرور فعال نیست.');
        }

        $keyResource = openssl_pkey_get_private($privateKey);

        if ($keyResource === false) {
            throw new RuntimeException('فرمت کلید خصوصی GSC_PRIVATE_KEY در فایل .env نامعتبر است.');
        }

        $now = time();
        $header = $this->base64UrlEncode((string) json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));
        $payload = $this->base64UrlEncode((string) json_encode([
            'iss' => $email,
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_AUDIENCE,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "$header.$payload";
        $signature = '';

        if (! openssl_sign($signingInput, $signature, $keyResource, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('امضای امنیتی توکن JWT برای گوگل ناموفق بود.');
        }

        return "$signingInput." . $this->base64UrlEncode($signature);
    }

    private function extractRows(mixed $response): array
    {
        if (! $response instanceof Response || $response->failed()) {
            return [];
        }

        $rows = data_get($response->json(), 'rows', []);

        return is_array($rows) ? $rows : [];
    }

    private function extractSitemaps(mixed $response): array
    {
        if (! $response instanceof Response || $response->failed()) {
            return [];
        }

        $items = data_get($response->json(), 'sitemap', []);

        return is_array($items) ? $items : [];
    }

    private function describeApiError(mixed $response): string
    {
        if (! $response instanceof Response) {
            return 'پاسخ معتبری از Google Search Console دریافت نشد.';
        }

        $status = $response->status();
        $json = $response->json();
        $message = is_array($json) ? trim(strip_tags((string) data_get($json, 'error.message', ''))) : '';

        if ($message === '') {
            $raw = preg_replace('/<(style|script|head)\b[^>]*>.*?<\/\1>/is', ' ', $response->body()) ?? $response->body();
            $message = trim(preg_replace('/\s+/', ' ', strip_tags($raw)) ?? '');
        }

        $shortDetail = $message !== '' ? ' (' . mb_substr($message, 0, 240) . ')' : '';

        if ($status === 403) {
            if (! is_array($json) && str_contains(strtolower($message), 'does not have permission to get url')) {
                return 'دسترسی IP سرور به searchconsole.googleapis.com توسط گوگل محدود شده است (تحریم IP). مقدار GSC_API_BASE_URL را روی پروکسی خود تنظیم کنید.' . $shortDetail;
            }

            return 'دسترسی به این ملک در سرچ کنسول رد شد (403).' . $shortDetail;
        }

        if ($status === 404) {
            return 'آدرس سایت ثبت‌شده در GSC_SITE_URL در سرچ کنسول یافت نشد (404).' . $shortDetail;
        }

        if ($status === 401) {
            return 'احراز هویت گوگل سرچ کنسول رد شد (401).' . $shortDetail;
        }

        return "خطای Google Search Console (HTTP $status)" . $shortDetail;
    }

    private function clientEmail(): string
    {
        return trim((string) config('services.gsc.client_email', ''));
    }

    private function privateKey(): string
    {
        $raw = trim((string) config('services.gsc.private_key', ''));
        $raw = trim($raw, "\"'");

        return str_replace(['\\r\\n', '\\n', '\\r'], "\n", $raw);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
