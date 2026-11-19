<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McpOAuthClient extends Model
{
    protected $table = 'mcp_oauth_clients';

    protected $fillable = [
        'client_id',
        'client_secret_hash',
        'name',
        'redirect_uris',
        'grant_types',
        'token_endpoint_auth_method',
        'is_cimd',
    ];

    protected $hidden = [
        'client_secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'redirect_uris' => 'array',
            'grant_types' => 'array',
            'is_cimd' => 'boolean',
        ];
    }

    public function allowsRedirectUri(string $uri): bool
    {
        $allowed = is_array($this->redirect_uris) ? $this->redirect_uris : [];

        foreach ($allowed as $registered) {
            if (! is_string($registered) || $registered === '') {
                continue;
            }

            if ($registered === $uri) {
                return true;
            }

            if ($this->matchesLoopbackRedirect($registered, $uri)) {
                return true;
            }
        }

        return false;
    }

    private function matchesLoopbackRedirect(string $registered, string $candidate): bool
    {
        $reg = parse_url($registered);
        $cand = parse_url($candidate);

        if (! is_array($reg) || ! is_array($cand)) {
            return false;
        }

        $regScheme = strtolower((string) ($reg['scheme'] ?? ''));
        $candScheme = strtolower((string) ($cand['scheme'] ?? ''));

        if ($regScheme !== 'http' || $candScheme !== 'http') {
            return false;
        }

        $regHost = strtolower((string) ($reg['host'] ?? ''));
        $candHost = strtolower((string) ($cand['host'] ?? ''));
        $loopbacks = ['localhost', '127.0.0.1', '[::1]'];

        if (! in_array($regHost, $loopbacks, true) || $regHost !== $candHost) {
            return false;
        }

        return ($reg['path'] ?? '/') === ($cand['path'] ?? '/');
    }
}
