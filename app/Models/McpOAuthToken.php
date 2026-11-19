<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpOAuthToken extends Model
{
    protected $table = 'mcp_oauth_tokens';

    protected $fillable = [
        'user_id',
        'client_id',
        'access_token_hash',
        'refresh_token_hash',
        'scopes',
        'resource',
        'access_expires_at',
        'refresh_expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'access_token_hash',
        'refresh_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccessValid(): bool
    {
        return $this->revoked_at === null
            && $this->access_expires_at !== null
            && $this->access_expires_at->isFuture();
    }

    public function isRefreshValid(): bool
    {
        return $this->revoked_at === null
            && $this->refresh_token_hash !== null
            && $this->refresh_expires_at !== null
            && $this->refresh_expires_at->isFuture();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, is_array($this->scopes) ? $this->scopes : [], true);
    }

    public function touchLastUsed(): void
    {
        $this->updateQuietly(['last_used_at' => now()]);
    }
}
