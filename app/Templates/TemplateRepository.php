<?php

namespace App\Templates;

use App\Docs\DocumentationRepository;
use App\Templates\Catalog\WooCommerceOrderTelegramTableTemplate;
use App\Templates\Contracts\WorkflowTemplateContract;
use App\Templates\DTOs\WorkflowTemplate;
use App\Templates\Enums\TemplateCategory;
use ReflectionClass;

final class TemplateRepository
{
    /** @var array<int, class-string<WorkflowTemplateContract>> */
    private const CATALOG = [
        WooCommerceOrderTelegramTableTemplate::class,
    ];

    /** @var array<string, WorkflowTemplate>|null */
    private static ?array $cache = null;

    /** @var array<string, class-string<WorkflowTemplateContract>>|null */
    private static ?array $classMap = null;

    /** @return array<int, WorkflowTemplate> */
    public static function all(): array
    {
        self::boot();

        return array_values(self::$cache);
    }

    public static function find(string $slug): ?WorkflowTemplate
    {
        self::boot();

        return self::$cache[$slug] ?? null;
    }

    /** @return array<int, array{category: TemplateCategory, count: int}> */
    public static function categoriesWithCounts(): array
    {
        $counts = [];

        foreach (self::all() as $template) {
            $key = $template->category->value;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $result = [];

        foreach (TemplateCategory::cases() as $category) {
            if (($counts[$category->value] ?? 0) > 0) {
                $result[] = [
                    'category' => $category,
                    'count' => $counts[$category->value],
                ];
            }
        }

        return $result;
    }

    /** @return array<int, array{key: string, name: string, icon: string, count: int}> */
    public static function appsWithCounts(): array
    {
        $apps = [];

        foreach (self::all() as $template) {
            foreach ($template->requiredApps as $app) {
                $key = $app['key'];
                if (! isset($apps[$key])) {
                    $apps[$key] = [
                        'key' => $key,
                        'name' => $app['name'],
                        'icon' => $app['icon'],
                        'count' => 0,
                    ];
                }
                $apps[$key]['count']++;
            }
        }

        return array_values($apps);
    }

    /** @return array<int, array<string, mixed>> */
    public static function relatedDocsFor(WorkflowTemplate $template): array
    {
        $docs = [];

        foreach ($template->relatedDocSlugs as $docSlug) {
            $article = DocumentationRepository::find($docSlug);
            if ($article !== null) {
                $docs[] = $article;
            }
        }

        return $docs;
    }

    /** @return array<int, WorkflowTemplate> */
    public static function relatedTemplatesFor(WorkflowTemplate $template, int $limit = 3): array
    {
        $picked = [];

        foreach ($template->relatedTemplateSlugs as $slug) {
            if ($slug === $template->slug) {
                continue;
            }

            $found = self::find($slug);
            if ($found !== null) {
                $picked[$found->slug] = $found;
            }
        }

        if (count($picked) < $limit) {
            foreach (self::all() as $candidate) {
                if ($candidate->slug === $template->slug || isset($picked[$candidate->slug])) {
                    continue;
                }

                if ($candidate->category === $template->category) {
                    $picked[$candidate->slug] = $candidate;
                }

                if (count($picked) >= $limit) {
                    break;
                }
            }
        }

        if (count($picked) < $limit) {
            foreach (self::all() as $candidate) {
                if ($candidate->slug === $template->slug || isset($picked[$candidate->slug])) {
                    continue;
                }

                $picked[$candidate->slug] = $candidate;

                if (count($picked) >= $limit) {
                    break;
                }
            }
        }

        return array_slice(array_values($picked), 0, $limit);
    }

    public static function sourceFilePath(string $slug): ?string
    {
        self::boot();

        $class = self::$classMap[$slug] ?? null;

        if ($class === null) {
            return null;
        }

        $file = (new ReflectionClass($class))->getFileName();

        return is_string($file) ? $file : null;
    }

    private static function boot(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];
        self::$classMap = [];

        foreach (self::CATALOG as $class) {
            $template = $class::make();
            self::$cache[$template->slug] = $template;
            self::$classMap[$template->slug] = $class;
        }
    }
}
