<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 255)->unique();
            $table->string('client_secret_hash', 255)->nullable();
            $table->string('name', 150);
            $table->json('redirect_uris');
            $table->json('grant_types');
            $table->string('token_endpoint_auth_method', 50)->default('none');
            $table->boolean('is_cimd')->default(false);
            $table->timestamps();
        });

        Schema::create('mcp_oauth_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_hash', 64)->unique();
            $table->string('client_id', 255)->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('redirect_uri', 500);
            $table->string('code_challenge', 128);
            $table->string('code_challenge_method', 16)->default('S256');
            $table->json('scopes');
            $table->string('resource', 500)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mcp_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_id', 255)->index();
            $table->string('access_token_hash', 64)->unique();
            $table->string('refresh_token_hash', 64)->nullable()->unique();
            $table->json('scopes');
            $table->string('resource', 500)->nullable();
            $table->timestamp('access_expires_at')->index();
            $table->timestamp('refresh_expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_oauth_tokens');
        Schema::dropIfExists('mcp_oauth_codes');
        Schema::dropIfExists('mcp_oauth_clients');
    }
};
