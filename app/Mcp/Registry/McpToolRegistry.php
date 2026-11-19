<?php

namespace App\Mcp\Registry;

use App\Mcp\Contracts\McpToolContract;
use App\Models\McpOAuthToken;
use InvalidArgumentException;

final class McpToolRegistry
{
    private array $tools = [];

    public function __construct(array $toolClasses = [])
    {
        foreach ($toolClasses as $toolClass) {
            $this->register($toolClass);
        }
    }

    public function register(string $toolClass): void
    {
        if (! is_subclass_of($toolClass, McpToolContract::class)) {
            throw new InvalidArgumentException("$toolClass must implement McpToolContract.");
        }

        $instance = app($toolClass);
        $this->tools[$instance->name()] = $toolClass;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function resolve(string $name): McpToolContract
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException("Unknown MCP tool: $name");
        }

        return app($this->tools[$name]);
    }

    public function listDefinitions(?McpOAuthToken $token = null): array
    {
        $definitions = [];

        foreach ($this->tools as $toolClass) {
            $tool = app($toolClass);

            if ($token !== null && ! $token->hasScope($tool->requiredScope())) {
                continue;
            }

            $definitions[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
                'annotations' => $tool->annotations(),
            ];
        }

        return $definitions;
    }
}
