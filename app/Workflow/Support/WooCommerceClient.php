<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class WooCommerceClient
{
    private string $storeUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private string $authMode;

    public function __construct(private readonly Credential $credential)
    {
        if ($this->credential->type !== 'woocommerce') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی برای WooCommerce معتبر نیست.');
        }

        $rawUrl = trim((string) $this->credential->get('url', ''));
        $this->consumerKey = trim((string) $this->credential->get('consumer_key', ''));
        $this->consumerSecret = trim((string) $this->credential->get('consumer_secret', ''));
        $this->authMode = trim((string) $this->credential->get('auth_mode', 'auto')) ?: 'auto';

        if ($rawUrl === '' || $this->consumerKey === '' || $this->consumerSecret === '') {
            throw NodeExecutionException::because('اطلاعات اعتبارنامه ووکامرس (آدرس سایت یا کلیدهای API) ناقص است.');
        }

        $this->storeUrl = self::normalizeStoreUrl($rawUrl);

        (new SafeHttpClient())->assertSafeUrl($this->storeUrl);
    }

    public static function normalizeStoreUrl(string $url): string
    {
        $clean = rtrim(trim($url), '/');
        $clean = preg_replace('#/wp-json(/wc/v\d+)?/?$#i', '', $clean) ?? $clean;

        return rtrim($clean, '/');
    }

    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    public function post(string $path, array $body = [], array $query = []): array
    {
        return $this->send('POST', $path, $query, $body);
    }

    public function put(string $path, array $body = [], array $query = []): array
    {
        return $this->send('PUT', $path, $query, $body);
    }

    public function delete(string $path, array $query = ['force' => true]): array
    {
        return $this->send('DELETE', $path, $query);
    }

    public function listWebhooks(int $perPage = 100): array
    {
        $items = $this->get('webhooks', [
            'per_page' => max(1, min(100, $perPage)),
            'status'   => 'all',
        ]);

        return array_is_list($items) ? $items : [];
    }

    public function syncWebhook(string $name, string $topic, string $deliveryUrl, string $secret): array
    {
        try {
            foreach ($this->listWebhooks() as $hook) {
                if (! is_array($hook)) {
                    continue;
                }

                $existingUrl = rtrim((string) ($hook['delivery_url'] ?? ''), '/');
                $targetUrl = rtrim($deliveryUrl, '/');

                if ($existingUrl !== '' && $existingUrl === $targetUrl && isset($hook['id'])) {
                    return $this->put("webhooks/{$hook['id']}", [
                        'name'         => $name,
                        'status'       => 'active',
                        'topic'        => $topic,
                        'delivery_url' => $deliveryUrl,
                        'secret'       => $secret,
                    ]);
                }
            }
        } catch (Throwable) {
        }

        return $this->post('webhooks', [
            'name'         => $name,
            'status'       => 'active',
            'topic'        => $topic,
            'delivery_url' => $deliveryUrl,
            'secret'       => $secret,
        ]);
    }

    public function removeWebhooksByUrl(string $deliveryUrl): void
    {
        $targetUrl = rtrim($deliveryUrl, '/');

        foreach ($this->listWebhooks() as $hook) {
            if (! is_array($hook) || ! isset($hook['id'])) {
                continue;
            }

            $existingUrl = rtrim((string) ($hook['delivery_url'] ?? ''), '/');

            if ($existingUrl !== '' && $existingUrl === $targetUrl) {
                try {
                    $this->delete("webhooks/{$hook['id']}", ['force' => true]);
                } catch (Throwable) {
                }
            }
        }
    }

    private function send(string $method, string $path, array $query = [], array $body = []): array
    {
        $endpoint = "$this->storeUrl/wp-json/wc/v3/" . ltrim($path, '/');
        $isHttps = str_starts_with(strtolower($this->storeUrl), 'https://');

        if ($this->authMode === 'query' || ($this->authMode === 'auto' && $isHttps)) {
            $query['consumer_key'] = $this->consumerKey;
            $query['consumer_secret'] = $this->consumerSecret;
        }

        $urlWithQuery = $query !== []
            ? $endpoint . (str_contains($endpoint, '?') ? '&' : '?') . http_build_query($query)
            : $endpoint;

        $request = $this->httpClient();

        try {
            $response = match (strtoupper($method)) {
                'GET'    => $request->get($urlWithQuery),
                'POST'   => $request->post($urlWithQuery, $body),
                'PUT'    => $request->put($urlWithQuery, $body),
                'DELETE' => $request->delete($urlWithQuery, $body),
                default  => throw NodeExecutionException::because("متد نامعتبر: $method"),
            };
        } catch (NodeExecutionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw NodeExecutionException::because('خطا در برقراری ارتباط با فروشگاه ووکامرس: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw NodeExecutionException::because($this->describeError($response));
        }

        $json = $response->json();

        return is_array($json) ? $json : ['result' => $json];
    }

    private function httpClient(): PendingRequest
    {
        $client = Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'User-Agent' => 'TameshkFlow-WooCommerce/1.0',
            ])
            ->timeout(35);

        if ($this->authMode === 'header' || $this->authMode === 'auto') {
            $client = $client->withBasicAuth($this->consumerKey, $this->consumerSecret);
        }

        return $client;
    }

    private function describeError(Response $response): string
    {
        $status = $response->status();
        $json = $response->json();
        $code = is_array($json) ? (string) ($json['code'] ?? '') : '';
        $message = is_array($json) ? trim(strip_tags((string) ($json['message'] ?? ''))) : '';

        if ($status === 401 || $status === 403 || str_contains($code, 'cannot_')) {
            $detail = $message !== '' ? " ($message)" : '';

            return "خطای احراز هویت ووکامرس (HTTP $status): کلیدهای API نامعتبر هستند یا سطح دسترسی کلید روی «خواندن/نوشتن» (Read/Write) تنظیم نشده است.$detail";
        }

        if ($status === 404) {
            if ($code === 'rest_no_route') {
                return 'مسیر API ووکامرس در این سایت یافت نشد. مطمئن شوید افزونه WooCommerce فعال است و پیوندهای یکتا (Permalinks) در وردپرس روی حالت «ساده» نیست.';
            }

            return $message !== ''
                ? "مورد درخواستی در ووکامرس یافت نشد: $message"
                : 'آیتم مورد نظر (سفارش، محصول یا مشتری) با این شناسه در ووکامرس پیدا نشد.';
        }

        if ($message !== '') {
            return "خطای ووکامرس (HTTP $status): $message";
        }

        return "فروشگاه ووکامرس با کد خطای HTTP $status پاسخ داد.";
    }
}
