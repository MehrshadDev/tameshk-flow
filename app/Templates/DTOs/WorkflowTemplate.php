<?php

namespace App\Templates\DTOs;

use App\Templates\Enums\TemplateCategory;

final readonly class WorkflowTemplate
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $outcomes
     * @param  array<int, array{title: string, description: string}>  $setupSteps
     * @param  array<int, array{q: string, a: string}>  $faqs
     * @param  array<int, array{key: string, name: string, icon: string, role: string}>  $requiredApps
     * @param  array<int, string>  $relatedDocSlugs
     * @param  array<int, string>  $relatedTemplateSlugs
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $edges
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $subtitle,
        public string $seoTitle,
        public string $seoDescription,
        public TemplateCategory $category,
        public array $tags,
        public string $difficulty,
        public int $setupMinutes,
        public string $problem,
        public array $outcomes,
        public string $audience,
        public array $setupSteps,
        public array $faqs,
        public array $requiredApps,
        public array $relatedDocSlugs,
        public array $relatedTemplateSlugs,
        public string $workflowName,
        public string $workflowDescription,
        public array $nodes,
        public array $edges,
        public bool $featured = false,
    ) {}

    public function appKeys(): array
    {
        return array_values(array_unique(array_column($this->requiredApps, 'key')));
    }

    public function nodeCount(): int
    {
        return count(array_filter(
            $this->nodes,
            fn (array $node) => ($node['type'] ?? '') !== 'sticky_note',
        ));
    }

    public function searchableText(): string
    {
        return mb_strtolower(implode(' ', [
            $this->title,
            $this->subtitle,
            $this->category->label(),
            implode(' ', $this->tags),
            implode(' ', array_column($this->requiredApps, 'name')),
        ]));
    }
}
