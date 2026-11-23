<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Workflow\Support\GmailClient;
use App\Workflow\Support\GoogleSheetsClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleOAuthController extends Controller
{
    private const SCOPES = [
        'gmail' => [
            'https://mail.google.com/',
            'https://www.googleapis.com/auth/userinfo.email',
        ],
        'google' => [
            'https://www.googleapis.com/auth/spreadsheets',
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
        ],
    ];

    public function redirect(Request $request): RedirectResponse
    {
        $credentialId = (int) $request->query('credential_id');
        $credential = Credential::query()
            ->where('id', $credentialId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $clientId = $credential->get('client_id');

        if (empty($clientId)) {
            abort(400, 'فیلد Client ID ذخیره نشده است.');
        }

        $scopes = self::SCOPES[$credential->type] ?? null;

        if ($scopes === null) {
            abort(400, 'این نوع اعتبارنامه از اتصال گوگل پشتیبانی نمی‌کند.');
        }

        $state = Crypt::encryptString(json_encode([
            'credential_id' => $credential->id,
            'user_id' => $request->user()->id,
            'type' => $credential->type,
        ]));

        $authBase = rtrim((string) config('services.google.auth_base_url', 'https://accounts.google.com'), '/');

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect()->away($authBase . '/o/oauth2/v2/auth?' . $params);
    }

    public function callback(Request $request): Response
    {
        $code = (string) $request->query('code');
        $state = (string) $request->query('state');
        $type = 'gmail';
        $credentialId = 0;
        $userId = 0;

        if ($state !== '') {
            try {
                $payload = json_decode(Crypt::decryptString($state), true);
                $credentialId = (int) ($payload['credential_id'] ?? 0);
                $userId = (int) ($payload['user_id'] ?? 0);
                $type = (string) ($payload['type'] ?? 'gmail');
            } catch (Throwable) {
                return response(
                    $this->popupResponse(false, 'توکن امنیتی State نامعتبر است.', $type)
                );
            }
        }

        if ($code === '' || $state === '') {
            return response(
                $this->popupResponse(false, 'کد اعتبارسنجی گوگل دریافت نشد یا لغو شد.', $type)
            );
        }

        $credential = Credential::query()
            ->where('id', $credentialId)
            ->where('user_id', $userId)
            ->first();

        if (! $credential) {
            return response(
                $this->popupResponse(false, 'اعتبارنامه مربوطه یافت نشد.', $type)
            );
        }

        $type = $credential->type;
        $clientId = $credential->get('client_id');
        $clientSecret = $credential->get('client_secret');
        $oauthBase = rtrim((string) config('services.google.oauth_base_url', 'https://oauth2.googleapis.com'), '/');

        try {
            $tokenRes = Http::asForm()->timeout(20)->post($oauthBase . '/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code',
            ]);
        } catch (Throwable $e) {
            return response(
                $this->popupResponse(false, 'خطا در ارتباط با سرور گوگل: ' . $e->getMessage(), $type)
            );
        }

        if ($tokenRes->failed()) {
            $desc = (string) data_get($tokenRes->json(), 'error_description', $tokenRes->body());

            return response(
                $this->popupResponse(false, 'دریافت توکن گوگل با خطا مواجه شد: ' . $desc, $type)
            );
        }

        $tokenData = (array) $tokenRes->json();
        $accessToken = data_get($tokenData, 'access_token');

        if (empty($accessToken)) {
            return response(
                $this->popupResponse(false, 'توکن دسترسی گوگل دریافت نشد.', $type)
            );
        }

        $refreshToken = data_get($tokenData, 'refresh_token', $credential->get('refresh_token'));
        $expiresIn = (int) data_get($tokenData, 'expires_in', 3600);

        $credentialData = $credential->data ?? [];
        $credentialData['access_token'] = $accessToken;

        if (! empty($refreshToken)) {
            $credentialData['refresh_token'] = $refreshToken;
        }

        $credentialData['expires_at'] = now()->addSeconds(max(60, $expiresIn - 60))->toIso8601String();

        $credential->update(['data' => $credentialData]);

        $email = $this->resolveEmail($credential, $type);

        if ($email !== '') {
            $credentialData['connected_email'] = $email;
            $credential->update(['data' => $credentialData]);
        }

        $successMessage = $type === 'gmail'
            ? 'حساب جیمیل با موفقیت متصل شد!'
            : 'حساب گوگل با موفقیت متصل شد و دسترسی Google Sheets اعطا شد!';

        return response(
            $this->popupResponse(true, $successMessage, $type)
        );
    }

    private function resolveEmail(Credential $credential, string $type): string
    {
        if ($type === 'gmail') {
            try {
                $profile = (new GmailClient($credential))->getProfile();

                return (string) data_get($profile, 'emailAddress', '');
            } catch (Throwable) {
                return '';
            }
        }

        try {
            $token = (new GoogleSheetsClient($credential))->getValidAccessToken();
            $apiBase = rtrim((string) config('services.google.api_base_url', 'https://www.googleapis.com'), '/');
            $response = Http::withToken($token)->timeout(10)->get($apiBase . '/oauth2/v3/userinfo');

            if ($response->successful()) {
                return (string) data_get($response->json(), 'email', '');
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function getRedirectUri(): string
    {
        if ($configured = config('services.google.oauth_redirect_uri')) {
            return rtrim((string) $configured, '/');
        }

        $base = rtrim((string) (config('app.url') ?: url('/')), '/');

        return $base . route('oauth.google.callback', [], false);
    }

    private function popupResponse(bool $success, string $message, string $type = 'gmail'): string
    {
        $statusStr = $success ? 'success' : 'error';
        $eventType = $type === 'gmail' ? 'GMAIL_OAUTH_RESULT' : 'GOOGLE_OAUTH_RESULT';
        $title = $type === 'gmail' ? 'اتصال حساب جیمیل' : 'اتصال حساب گوگل';

        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $jsPayload = json_encode([
            'type' => $eventType,
            'status' => $statusStr,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        return <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>احراز هویت گوگل</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #0a0a0a; color: #fff; text-align: center; }
        .box { padding: 2rem; border-radius: 1rem; border: 1px solid #2e2e2e; background: #141414; max-width: 400px; }
        .msg { font-size: 0.95rem; line-height: 1.6; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="box">
    <h3>$safeTitle</h3>
    <p class="msg">$safeMessage</p>
    <p style="font-size: 0.8rem; opacity: 0.6; margin-top: 1.5rem;">این پنجره به‌صورت خودکار بسته می‌شود...</p>
</div>
<script>
    if (window.opener) {
        window.opener.postMessage($jsPayload, '*');
        setTimeout(() => window.close(), 1500);
    }
</script>
</body>
</html>
HTML;
    }
}
