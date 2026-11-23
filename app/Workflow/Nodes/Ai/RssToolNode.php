<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\RssFeedClient;

class RssToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.rss',
            name: 'RSS Feed Tool',
            category: NodeCategory::Ai,
            description: 'ابزار خواندن و جستجوی آخرین اخبار و مقالات از خوراک RSS یا Atom توسط ایجنت هوش مصنوعی.',
            icon: 'rss',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'tool_name' => [
                    'type' => 'text',
                    'label' => 'نام ابزار (انگلیسی)',
                    'default' => 'read_rss_feed',
                    'required' => true,
                    'placeholder' => 'read_rss_feed',
                ],
                'tool_description' => [
                    'type' => 'textarea',
                    'label' => 'توضیحات عملکرد ابزار برای مدل',
                    'default' => 'خواندن و جستجوی آخرین اخبار، مقالات و مطالب منتشرشده از یک خوراک RSS یا Atom.',
                    'required' => true,
                ],
                'default_feed_url' => [
                    'type' => 'text',
                    'label' => 'آدرس خوراک پیش‌فرض (اختیاری)',
                    'required' => false,
                    'placeholder' => 'https://example.com/feed',
                    'help' => 'در صورت پر بودن، اگر ایجنت آدرس فید ارسال نکند از این آدرس استفاده می‌شود.',
                ],
                'limit' => [
                    'type' => 'number',
                    'label' => 'حداکثر تعداد مطالب در هر درخواست',
                    'default' => 5,
                    'min' => 1,
                    'max' => 20,
                    'integer' => true,
                    'advanced' => true,
                ],
            ],
            color: 'orange',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'read_rss_feed'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'خواندن و جستجوی آخرین مطالب از خوراک RSS'));
    }

    public function toolParameters(array $config): array
    {
        $hasDefault = trim((string) ($config['default_feed_url'] ?? '')) !== '';

        $properties = [
            'feed_url' => [
                'type' => 'STRING',
                'description' => $hasDefault
                    ? 'آدرس اینترنتی خوراک RSS یا Atom (اختیاری؛ در صورت خالی بودن از خوراک پیش‌فرض استفاده می‌شود)'
                    : 'آدرس کامل اینترنتی خوراک RSS یا Atom (شروع با http:// یا https://)',
            ],
            'keywords' => [
                'type' => 'STRING',
                'description' => 'کلمات کلیدی اختیاری برای فیلتر کردن مطالب خوراک (جداشده با کاما)',
            ],
            'limit' => [
                'type' => 'INTEGER',
                'description' => 'حداکثر تعداد مطالب جدید برای دریافت (بین ۱ تا ۲۰)',
            ],
        ];

        return [
            'type' => 'OBJECT',
            'properties' => $properties,
            'required' => $hasDefault ? [] : ['feed_url'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $feedUrl = trim((string) ($arguments['feed_url'] ?? ''));

        if ($feedUrl === '') {
            $feedUrl = trim((string) ($context->config['default_feed_url'] ?? ''));
        }

        if ($feedUrl === '') {
            throw NodeExecutionException::because('آدرس خوراک RSS (feed_url) مشخص نشده است.');
        }

        $defaultLimit = max(1, min(20, (int) ($context->config['limit'] ?? 5)));
        $limit = isset($arguments['limit']) && is_numeric($arguments['limit'])
            ? max(1, min(20, (int) $arguments['limit']))
            : $defaultLimit;

        $keywords = trim((string) ($arguments['keywords'] ?? ''));

        $result = (new RssFeedClient())->fetch(
            url: $feedUrl,
            limit: $limit,
            keywords: $keywords,
        );

        $compactItems = array_map(fn (array $item) => [
            'title' => $item['title'],
            'link' => $item['link'],
            'description' => mb_substr($item['description'], 0, 500),
            'author' => $item['author'],
            'categories' => $item['categories'],
            'image' => $item['image'],
            'published_at' => $item['published_at'],
            'persian_date' => $item['persian_date'],
        ], $result['items']);

        return [
            'feed' => $result['feed'],
            'count' => count($compactItems),
            'items' => $compactItems,
        ];
    }
}
