<?php

namespace App\Workflow\Support;

use App\Workflow\Exceptions\NodeExecutionException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

final class RssFeedClient
{
    private const MAX_BODY_BYTES = 5242880;
    private const MAX_REDIRECTS = 3;
    private const DEFAULT_TIMEOUT = 20;

    public function fetch(
        string $url,
        int $limit = 20,
        string $keywords = '',
        ?string $conditionalCacheKey = null,
        int $timeout = self::DEFAULT_TIMEOUT,
    ): array {
        $cleanUrl = trim($url);

        if ($cleanUrl === '') {
            throw NodeExecutionException::because('آدرس خوراک RSS الزامی است.');
        }

        $safeLimit = max(1, min(100, $limit));
        $fetched = $this->downloadFeed($cleanUrl, $conditionalCacheKey, $timeout);

        if ($fetched['not_modified']) {
            return [
                'not_modified' => true,
                'feed' => [],
                'items' => [],
                'count' => 0,
            ];
        }

        $parsed = $this->parseXml($fetched['body'], $fetched['final_url']);
        $items = $parsed['items'];

        if (trim($keywords) !== '') {
            $items = $this->filterByKeywords($items, $keywords);
        }

        usort($items, fn (array $a, array $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        $sliced = array_values(array_slice($items, 0, $safeLimit));

        return [
            'not_modified' => false,
            'feed' => $parsed['feed'],
            'items' => $sliced,
            'count' => count($sliced),
        ];
    }

    private function downloadFeed(string $url, ?string $conditionalCacheKey, int $timeout): array
    {
        $safeHttp = new SafeHttpClient();
        $currentUrl = $url;
        $cachedValidators = $conditionalCacheKey !== null ? Cache::get($conditionalCacheKey) : null;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $safeHttp->assertSafeUrl($currentUrl);

            $headers = [
                'User-Agent' => 'TameshkFlow-RSS/1.0',
                'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
            ];

            if ($hop === 0 && is_array($cachedValidators)) {
                if (! empty($cachedValidators['etag'])) {
                    $headers['If-None-Match'] = (string) $cachedValidators['etag'];
                }

                if (! empty($cachedValidators['last_modified'])) {
                    $headers['If-Modified-Since'] = (string) $cachedValidators['last_modified'];
                }
            }

            try {
                $response = Http::withHeaders($headers)
                    ->withOptions([
                        'allow_redirects' => false,
                        'verify' => true,
                    ])
                    ->timeout(max(5, min(45, $timeout)))
                    ->get($currentUrl);
            } catch (Throwable) {
                throw NodeExecutionException::because('امکان برقراری ارتباط با آدرس خوراک RSS وجود ندارد.');
            }

            $status = $response->status();

            if ($status === 304) {
                return [
                    'not_modified' => true,
                    'body' => '',
                    'final_url' => $currentUrl,
                ];
            }

            if (in_array($status, [301, 302, 303, 307, 308], true)) {
                $location = trim((string) $response->header('Location'));

                if ($location === '' || $hop === self::MAX_REDIRECTS) {
                    throw NodeExecutionException::because('تعداد تغییر مسیرهای آدرس خوراک RSS بیش از حد مجاز است.');
                }

                $currentUrl = $this->resolveRedirectUrl($currentUrl, $location);
                continue;
            }

            if ($response->failed()) {
                throw NodeExecutionException::because("دریافت خوراک RSS با کد خطای HTTP $status ناموفق بود.");
            }

            $body = $response->body();

            if (trim($body) === '') {
                throw NodeExecutionException::because('محتوای دریافتی از آدرس خوراک RSS خالی است.');
            }

            if (strlen($body) > self::MAX_BODY_BYTES) {
                throw NodeExecutionException::because('حجم فایل خوراک RSS بیش از سقف مجاز (۵ مگابایت) است.');
            }

            if ($conditionalCacheKey !== null) {
                $etag = trim($response->header('ETag'));
                $lastModified = trim($response->header('Last-Modified'));

                if ($etag !== '' || $lastModified !== '') {
                    Cache::put($conditionalCacheKey, [
                        'etag' => $etag,
                        'last_modified' => $lastModified,
                    ], now()->addDays(7));
                }
            }

            return [
                'not_modified' => false,
                'body' => $body,
                'final_url' => $currentUrl,
            ];
        }

        throw NodeExecutionException::because('دریافت خوراک RSS ناموفق بود.');
    }

    private function parseXml(string $rawBody, string $feedUrl): array
    {
        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $rawBody) ?? $rawBody;
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean) ?? $clean;
        $clean = trim($clean);

        $previousState = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $xml = simplexml_load_string(
                $clean,
                SimpleXMLElement::class,
                LIBXML_NOCDATA | LIBXML_NONET | LIBXML_NOBLANKS
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        if (! $xml instanceof SimpleXMLElement) {
            throw NodeExecutionException::because('ساختار XML آدرس واردشده یک خوراک RSS یا Atom معتبر نیست.');
        }

        $rootName = strtolower($xml->getName());

        if ($rootName === 'feed') {
            return $this->parseAtom($xml, $feedUrl);
        }

        if ($rootName === 'rdf') {
            return $this->parseRdf($xml, $feedUrl);
        }

        if (isset($xml->channel)) {
            return $this->parseRss($xml, $feedUrl);
        }

        throw NodeExecutionException::because('فرمت خوراک پشتیبانی نمی‌شود. فقط خوراک‌های استاندارد RSS و Atom قابل خواندن هستند.');
    }

    private function parseRss(SimpleXMLElement $xml, string $feedUrl): array
    {
        $channel = $xml->channel;

        $feed = [
            'title' => $this->cleanText((string) ($channel->title ?? '')),
            'link' => trim((string) ($channel->link ?? '')),
            'description' => $this->cleanText((string) ($channel->description ?? '')),
            'language' => trim((string) ($channel->language ?? '')),
            'feed_url' => $feedUrl,
            'format' => 'rss',
        ];

        $items = [];
        $seen = [];

        foreach ($channel->item ?? [] as $item) {
            $parsed = $this->normalizeRssItem($item, $feed['title']);

            if ($parsed !== null && ! isset($seen[$parsed['hash']])) {
                $seen[$parsed['hash']] = true;
                $items[] = $parsed;
            }
        }

        return [
            'feed' => $feed,
            'items' => $items,
        ];
    }

    private function parseRdf(SimpleXMLElement $xml, string $feedUrl): array
    {
        $namespaces = $xml->getNamespaces(true);
        $defaultNs = $namespaces[''] ?? 'http://purl.org/rss/1.0/';
        $children = $xml->children($defaultNs);
        $channel = $children->channel ?? $xml->channel;

        $feed = [
            'title' => $this->cleanText((string) ($channel->title ?? '')),
            'link' => trim((string) ($channel->link ?? '')),
            'description' => $this->cleanText((string) ($channel->description ?? '')),
            'language' => '',
            'feed_url' => $feedUrl,
            'format' => 'rdf',
        ];

        $items = [];
        $seen = [];
        $rawItems = count($children->item ?? []) > 0 ? $children->item : ($xml->item ?? []);

        foreach ($rawItems as $item) {
            $parsed = $this->normalizeRssItem($item, $feed['title']);

            if ($parsed !== null && ! isset($seen[$parsed['hash']])) {
                $seen[$parsed['hash']] = true;
                $items[] = $parsed;
            }
        }

        return [
            'feed' => $feed,
            'items' => $items,
        ];
    }

    private function parseAtom(SimpleXMLElement $xml, string $feedUrl): array
    {
        $namespaces = $xml->getNamespaces(true);
        $atomNs = $namespaces[''] ?? 'http://www.w3.org/2005/Atom';
        $root = $xml->children($atomNs);

        if (count($root->entry ?? []) === 0 && count($xml->entry ?? []) > 0) {
            $root = $xml;
        }

        $feed = [
            'title' => $this->cleanText((string) ($root->title ?? '')),
            'link' => $this->extractAtomLink($root->link ?? null),
            'description' => $this->cleanText((string) ($root->subtitle ?? '')),
            'language' => '',
            'feed_url' => $feedUrl,
            'format' => 'atom',
        ];

        $items = [];
        $seen = [];

        foreach ($root->entry ?? [] as $entry) {
            $parsed = $this->normalizeAtomEntry($entry, $atomNs, $feed['title']);

            if ($parsed !== null && ! isset($seen[$parsed['hash']])) {
                $seen[$parsed['hash']] = true;
                $items[] = $parsed;
            }
        }

        return [
            'feed' => $feed,
            'items' => $items,
        ];
    }

    private function normalizeRssItem(SimpleXMLElement $item, string $feedTitle): ?array
    {
        $title = $this->cleanText((string) ($item->title ?? ''));
        $link = trim((string) ($item->link ?? ''));
        $guid = trim((string) ($item->guid ?? ''));

        if ($title === '' && $link === '' && $guid === '') {
            return null;
        }

        $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
        $dcNs = $item->children('http://purl.org/dc/elements/1.1/');
        $mediaNs = $item->children('http://search.yahoo.com/mrss/');

        $rawDescription = (string) ($item->description ?? '');
        $rawContent = (string) ($contentNs->encoded ?? '');

        if ($rawContent === '') {
            $rawContent = $rawDescription;
        }

        $author = $this->cleanText((string) ($dcNs->creator ?? ($item->author ?? '')));
        $rawDate = trim((string) ($item->pubDate ?? ($dcNs->date ?? '')));

        $categories = [];
        foreach ($item->category ?? [] as $cat) {
            $val = $this->cleanText((string) $cat);
            if ($val !== '') {
                $categories[] = $val;
            }
        }

        $image = $this->extractRssImage($item, $mediaNs, $rawContent, $rawDescription);

        return $this->buildItemRecord(
            id: $guid !== '' ? $guid : ($link !== '' ? $link : md5("$title|$rawDate")),
            title: $title,
            link: $link,
            rawDescription: $rawDescription,
            rawContent: $rawContent,
            author: $author,
            categories: array_values(array_unique($categories)),
            rawDate: $rawDate,
            image: $image,
            feedTitle: $feedTitle,
        );
    }

    private function normalizeAtomEntry(SimpleXMLElement $entry, string $atomNs, string $feedTitle): ?array
    {
        $e = $entry->children($atomNs);

        if ((string) ($e->title ?? '') === '' && (string) ($entry->title ?? '') !== '') {
            $e = $entry;
        }

        $title = $this->cleanText((string) ($e->title ?? ''));
        $link = $this->extractAtomLink($e->link ?? null);
        $id = trim((string) ($e->id ?? ''));

        if ($title === '' && $link === '' && $id === '') {
            return null;
        }

        $mediaNs = $entry->children('http://search.yahoo.com/mrss/');
        $rawSummary = (string) ($e->summary ?? '');
        $rawContent = (string) ($e->content ?? '');

        if ($rawContent === '') {
            $rawContent = $rawSummary;
        }

        $author = $this->cleanText((string) ($e->author->name ?? ($e->author ?? '')));
        $rawDate = trim((string) ($e->published ?? ($e->updated ?? '')));

        $categories = [];
        foreach ($e->category ?? [] as $cat) {
            $attrs = $cat->attributes();
            $label = $this->cleanText((string) ($attrs['label'] ?? ($attrs['term'] ?? (string) $cat)));
            if ($label !== '') {
                $categories[] = $label;
            }
        }

        $image = $this->extractMediaImage($mediaNs)
            ?? $this->extractImageFromHtml($rawContent)
            ?? $this->extractImageFromHtml($rawSummary);

        return $this->buildItemRecord(
            id: $id !== '' ? $id : ($link !== '' ? $link : md5("$title|$rawDate")),
            title: $title,
            link: $link,
            rawDescription: $rawSummary !== '' ? $rawSummary : $rawContent,
            rawContent: $rawContent,
            author: $author,
            categories: array_values(array_unique($categories)),
            rawDate: $rawDate,
            image: $image,
            feedTitle: $feedTitle,
        );
    }

    private function buildItemRecord(
        string $id,
        string $title,
        string $link,
        string $rawDescription,
        string $rawContent,
        string $author,
        array $categories,
        string $rawDate,
        ?string $image,
        string $feedTitle,
    ): array {
        $dateInfo = $this->parseDate($rawDate);
        $descriptionText = $this->htmlToText($rawDescription);
        $contentText = $this->htmlToText($rawContent);

        if ($descriptionText === '' && $contentText !== '') {
            $descriptionText = mb_substr($contentText, 0, 500);
        }

        return [
            'id' => $id,
            'hash' => sha1($id),
            'title' => $title,
            'link' => $link,
            'description' => mb_substr($descriptionText, 0, 2000),
            'content' => mb_substr($contentText, 0, 12000),
            'author' => $author,
            'categories' => $categories,
            'image' => $image,
            'published_at' => $dateInfo['iso'],
            'timestamp' => $dateInfo['timestamp'],
            'persian_date' => $dateInfo['persian_date'],
            'persian_ymd' => $dateInfo['persian_ymd'],
            'feed_title' => $feedTitle,
        ];
    }

    private function extractAtomLink(?SimpleXMLElement $links): string
    {
        if ($links === null) {
            return '';
        }

        $fallback = '';

        foreach ($links as $linkEl) {
            $attrs = $linkEl->attributes();
            $href = trim((string) ($attrs['href'] ?? ''));

            if ($href === '') {
                continue;
            }

            $rel = strtolower(trim((string) ($attrs['rel'] ?? 'alternate')));

            if ($rel === 'alternate' || $rel === '') {
                return $href;
            }

            if ($fallback === '') {
                $fallback = $href;
            }
        }

        return $fallback;
    }

    private function extractRssImage(
        SimpleXMLElement $item,
        SimpleXMLElement $mediaNs,
        string $rawContent,
        string $rawDescription,
    ): ?string {
        if (isset($item->enclosure)) {
            foreach ($item->enclosure as $enc) {
                $attrs = $enc->attributes();
                $url = trim((string) ($attrs['url'] ?? ''));
                $type = strtolower(trim((string) ($attrs['type'] ?? '')));

                if ($url !== '' && (str_starts_with($type, 'image/') || preg_match('/\.(jpe?g|png|webp|gif)(\?|$)/i', $url))) {
                    return $url;
                }
            }
        }

        return $this->extractMediaImage($mediaNs)
            ?? $this->extractImageFromHtml($rawContent)
            ?? $this->extractImageFromHtml($rawDescription);
    }

    private function extractMediaImage(SimpleXMLElement $mediaNs): ?string
    {
        foreach (['thumbnail', 'content'] as $tag) {
            if (! isset($mediaNs->{$tag})) {
                continue;
            }

            foreach ($mediaNs->{$tag} as $el) {
                $attrs = $el->attributes();
                $url = trim((string) ($attrs['url'] ?? ''));
                $medium = strtolower(trim((string) ($attrs['medium'] ?? '')));
                $type = strtolower(trim((string) ($attrs['type'] ?? '')));

                if ($url === '') {
                    continue;
                }

                if ($tag === 'thumbnail' || $medium === 'image' || str_starts_with($type, 'image/') || preg_match('/\.(jpe?g|png|webp|gif)(\?|$)/i', $url)) {
                    return $url;
                }
            }
        }

        return null;
    }

    private function extractImageFromHtml(string $html): ?string
    {
        if ($html === '') {
            return null;
        }

        if (preg_match('/<img[^>]+src=["\'](https?:\/\/[^"\'>\s]+)["\']/i', $html, $match)) {
            return html_entity_decode(trim($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    private function parseDate(string $rawDate): array
    {
        if ($rawDate === '') {
            return [
                'iso' => '',
                'timestamp' => 0,
                'persian_date' => '',
                'persian_ymd' => '',
            ];
        }

        try {
            $dt = Carbon::parse($rawDate)->setTimezone('Asia/Tehran');

            return [
                'iso' => $dt->toIso8601String(),
                'timestamp' => $dt->timestamp,
                'persian_date' => DateTimeHelper::toPersianDate($dt, 'yyyy/MM/dd HH:mm'),
                'persian_ymd' => DateTimeHelper::toPersianDate($dt, 'yyyy/MM/dd'),
            ];
        } catch (Throwable) {
            return [
                'iso' => $rawDate,
                'timestamp' => 0,
                'persian_date' => '',
                'persian_ymd' => '',
            ];
        }
    }

    private function filterByKeywords(array $items, string $rawKeywords): array
    {
        $terms = array_values(array_filter(array_map(
            'trim',
            preg_split('/[,،\r\n]+/u', $rawKeywords) ?: []
        )));

        if ($terms === []) {
            return $items;
        }

        return array_values(array_filter($items, function (array $item) use ($terms): bool {
            $haystack = implode(' ', [
                $item['title'] ?? '',
                $item['description'] ?? '',
                $item['content'] ?? '',
                implode(' ', (array) ($item['categories'] ?? [])),
            ]);

            foreach ($terms as $term) {
                if (mb_stripos($haystack, $term) !== false) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function htmlToText(string $raw): string
    {
        if (trim($raw) === '') {
            return '';
        }

        $clean = preg_replace('/<(script|style|noscript|iframe)\b[^>]*>.*?<\/\1>/is', ' ', $raw) ?? $raw;
        $clean = preg_replace('/<(br|p|div|li|h[1-6]|tr)\b[^>]*>/i', "\n", $clean) ?? $clean;
        $text = html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n\s*\n+/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function cleanText(string $raw): string
    {
        $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        if (preg_match('/^https?:\/\//i', $location)) {
            return $location;
        }

        $parsed = parse_url($baseUrl);

        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return $location;
        }

        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $origin = "{$parsed['scheme']}://{$parsed['host']}$port";

        if (str_starts_with($location, '//')) {
            return "{$parsed['scheme']}:$location";
        }

        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }

        $basePath = $parsed['path'] ?? '/';
        $dir = rtrim(dirname($basePath), '/');

        return $origin . ($dir !== '' ? $dir : '') . '/' . $location;
    }
}
