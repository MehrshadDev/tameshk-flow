<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\RssFeedClient;

class RssReadNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.rss',
            name: 'RSS Read',
            category: NodeCategory::Action,
            description: 'خواندن، استخراج و فیلتر کردن آخرین مطالب، اخبار و مقالات از هر خوراک RSS یا Atom.',
            icon: 'rss',
            group: 'rss',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'feed_url' => [
                    'type' => 'text',
                    'label' => 'آدرس خوراک (Feed URL)',
                    'required' => true,
                    'placeholder' => 'https://example.com/feed یا {{ input.url }}',
                ],
                'limit' => [
                    'type' => 'number',
                    'label' => 'حداکثر تعداد مطالب',
                    'default' => 10,
                    'min' => 1,
                    'max' => 50,
                    'integer' => true,
                    'required' => true,
                ],
                'keywords' => [
                    'type' => 'text',
                    'label' => 'فیلتر کلمات کلیدی (اختیاری)',
                    'placeholder' => 'هوش مصنوعی، استارتاپ، فناوری',
                    'help' => 'کلمات را با کاما جدا کنید تا فقط مطالب مرتبط برگردانده شوند.',
                    'advanced' => true,
                ],
                'since_hours' => [
                    'type' => 'number',
                    'label' => 'فقط مطالب چند ساعت اخیر (اختیاری)',
                    'placeholder' => 'مثلاً 24 برای مطالب ۲۴ ساعت گذشته',
                    'min' => 1,
                    'max' => 720,
                    'integer' => true,
                    'advanced' => true,
                ],
            ],
            color: 'orange',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $feedUrl = trim((string) ($context->config['feed_url'] ?? ''));
        $limit = max(1, min(50, (int) ($context->config['limit'] ?? 10)));
        $keywords = trim((string) ($context->config['keywords'] ?? ''));
        $sinceHours = isset($context->config['since_hours']) && is_numeric($context->config['since_hours'])
            ? max(0, (int) $context->config['since_hours'])
            : 0;

        if ($feedUrl === '') {
            throw NodeExecutionException::because('آدرس خوراک (Feed URL) الزامی است.');
        }

        $context->log("خواندن خوراک RSS از $feedUrl");

        $result = (new RssFeedClient())->fetch(
            url: $feedUrl,
            limit: $sinceHours > 0 ? 100 : $limit,
            keywords: $keywords,
        );

        $items = $result['items'];

        if ($sinceHours > 0) {
            $cutoff = time() - ($sinceHours * 3600);
            $items = array_values(array_filter(
                $items,
                fn (array $item) => (int) ($item['timestamp'] ?? 0) >= $cutoff
            ));
            $items = array_slice($items, 0, $limit);
        }

        $summaryLines = [];

        foreach ($items as $idx => $item) {
            $num = $idx + 1;
            $title = $item['title'] !== '' ? $item['title'] : 'بدون عنوان';
            $line = "$num. $title";

            if ($item['link'] !== '') {
                $line .= "\n{$item['link']}";
            }

            $summaryLines[] = $line;
        }

        return NodeResult::of([
            'feed' => $result['feed'],
            'count' => count($items),
            'items' => $items,
            'rows' => $items,
            'first' => $items[0] ?? null,
            'latest' => $items[0] ?? null,
            'summary_text' => implode("\n\n", $summaryLines),
        ]);
    }
}
