<?php

namespace App\Workflow\Registry;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\PollingTriggerContract;
use App\Workflow\DTOs\NodeDefinition;
use InvalidArgumentException;

final class NodeRegistry
{
    private array $nodes = [];

    public function __construct(array $nodeClasses = [])
    {
        foreach ($nodeClasses as $class) {
            $this->register($class);
        }
    }

    public function register(string $nodeClass): void
    {
        if (! is_subclass_of($nodeClass, NodeContract::class)) {
            throw new InvalidArgumentException("$nodeClass must implement NodeContract.");
        }

        $type = $nodeClass::definition()->type;
        $this->nodes[$type] = $nodeClass;
    }

    public function has(string $type): bool
    {
        return isset($this->nodes[$type]);
    }

    public function resolve(string $type): string
    {
        if (! $this->has($type)) {
            throw new InvalidArgumentException("Unknown node type: $type");
        }

        return $this->nodes[$type];
    }

    public function make(string $type): NodeContract
    {
        return app($this->resolve($type));
    }

    public function pollingTypes(): array
    {
        $types = [];

        foreach ($this->nodes as $type => $class) {
            if (is_subclass_of($class, PollingTriggerContract::class)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public function definitions(): array
    {
        $result = [];
        foreach ($this->nodes as $type => $class) {
            $result[$type] = $class::definition();
        }
        return $result;
    }

    public function definitionsByCategory(): array
    {
        $grouped = [];
        foreach ($this->definitions() as $def) {
            $grouped[$def->category->value][] = $def->toArray();
        }
        return $grouped;
    }
}
