<?php

namespace App\Mcp\Contracts;

use App\Models\User;

interface McpToolContract
{
    public function name(): string;

    public function description(): string;

    public function requiredScope(): string;

    public function inputSchema(): array;

    public function annotations(): array;

    public function execute(User $user, array $arguments): array;
}
