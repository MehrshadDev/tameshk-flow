<?php

namespace App\Workflow\Nodes\Triggers;

use App\Models\Credential;
use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\PollingTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\RssFeedClient;
use Illuminate\Support\Facades\Cache;

class RssTriggerNode implements NodeContract, PollingTriggerContract
{
    private const MAX_SEEN_HASHES = 300;

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'trigger.rss',
            name: 'On New RSS Item',
            category: NodeCategory::Trigger,
            description: 'آغاز خودکار ورکفلو به محض انتشار مطلب، خبر یا مقاله جدید در خوراک RSS یا Atom.',
            icon: 'rss',
            group: 'rss',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'feed_url' => [
                    'type' => 'text',
                    'label' => 'آدرس خوراک (Feed URL)',
                    'required' => true,
                    'placeholder' => 'https://example.com/feed',
                    'help' => 'آدرس کامل خوراک RSS یا Atom سایت یا وبلاگ مورد نظر را وارد کنید.',
                ],
                'poll_interval_minutes' => [
                    'type' => 'select',
                    'label' => 'بازه بررسی خوراک',
                    'options' => [
                        ['value' => '5', 'label' => 'هر ۵ دقیقه'],
                        ['value' => '15', 'label' => 'هر ۱۵ دقیقه (پیشنهادی)'],
                        ['value' => '30', 'label' => 'هر ۳۰ دقیقه'],
                        ['value' => '60', 'label' => 'هر ۱ ساعت'],
                        ['value' => '180', 'label' => 'هر ۳ ساعت'],
                        ['value' => '360', 'label' => 'هر ۶ ساعت'],
                    ],
                    'default' => '15',
                    'required' => true,
                ],
                'keywords' => [
                    'type' => 'text',
                    'label' => 'فیلتر کلمات کلیدی (اختیاری)',
                    'placeholder' => 'هوش مصنوعی، لاراول، PHP',
                    'help' => 'در صورت پر بودن، فقط مطالبی که حداقل یکی از این کلمات را داشته باشند ورکفلو را اجرا می‌کنند (با کاما جدا کنید).',
                    'advanced' => true,
                ],
                'max_items_per_poll' => [
                    'type' => 'number',
                    'label' => 'حداکثر تعداد مطلب جدید در هر نوبت',
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

    public static function feedUrlError(array $config): ?string
    {
        $url = trim((string) ($config['feed_url'] ?? ''));

        if ($url === '') {
            return 'آدرس خوراک (Feed URL) الزامی است.';
        }

        if (str_contains($url, '{{') || str_contains($url, '}}')) {
            return 'در نود شروع‌کننده امکان استفاده از متغیر وجود ندارد. یک آدرس اینترنتی ثابت وارد کنید.';
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'آدرس خوراک واردشده یک URL معتبر نیست.';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return 'آدرس خوراک باید با https:// یا http:// شروع شود.';
        }

        return null;
    }

    public function poll(Workflow $workflow, Node $trigger, ?Credential $credential): array
    {
        $config = is_array($trigger->config) ? $trigger->config : [];

        if (self::feedUrlError($config) !== null) {
            return [];
        }

        $feedUrl = trim((string) ($config['feed_url'] ?? ''));
        $intervalMinutes = max(5, min(1440, (int) ($config['poll_interval_minutes'] ?? 15)));
        $keywords = trim((string) ($config['keywords'] ?? ''));
        $maxPerPoll = max(1, min(20, (int) ($config['max_items_per_poll'] ?? 5)));

        $urlHash = md5($feedUrl);
        $lastCheckKey = "rss_trigger_last_check_{$workflow->id}_{$trigger->id}_{$urlHash}";
        $lastCheckedAt = (int) Cache::get($lastCheckKey, 0);

        if ($lastCheckedAt > 0 && (time() - $lastCheckedAt) < ($intervalMinutes * 60)) {
            return [];
        }

        $httpCacheKey = "rss_trigger_http_{$workflow->id}_{$trigger->id}_{$urlHash}";
        $result = (new RssFeedClient())->fetch(
            url: $feedUrl,
            limit: 50,
            keywords: $keywords,
            conditionalCacheKey: $httpCacheKey,
        );

        Cache::put($lastCheckKey, time(), now()->addDays(7));

        if ($result['not_modified'] || $result['items'] === []) {
            return [];
        }

        $seenKey = "rss_trigger_seen_{$workflow->id}_{$trigger->id}_" . md5($feedUrl . '|' . mb_strtolower($keywords));
        $seenHashes = Cache::get($seenKey);
        $currentHashes = array_column($result['items'], 'hash');

        if (! is_array($seenHashes)) {
            Cache::put($seenKey, array_slice($currentHashes, 0, self::MAX_SEEN_HASHES), now()->addDays(30));

            return [];
        }

        $seenLookup = array_flip($seenHashes);
        $newItems = [];

        foreach ($result['items'] as $item) {
            if (! isset($seenLookup[$item['hash']])) {
                $newItems[] = $item;
            }
        }

        if ($newItems === []) {
            Cache::put($seenKey, $seenHashes, now()->addDays(30));

            return [];
        }

        $mergedSeen = array_values(array_unique(array_merge($currentHashes, $seenHashes)));
        Cache::put($seenKey, array_slice($mergedSeen, 0, self::MAX_SEEN_HASHES), now()->addDays(30));

        $toDispatch = array_reverse(array_slice($newItems, 0, $maxPerPoll));
        $events = [];

        foreach ($toDispatch as $item) {
            $events[] = [
                ...$item,
                'feed' => $result['feed'],
            ];
        }

        return $events;
    }

    public function execute(NodeContext $context): NodeResult
    {
        if ($error = self::feedUrlError($context->config)) {
            throw NodeExecutionException::because($error);
        }

        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['id']) && (isset($input['title']) || isset($input['link']))) {
            return NodeResult::of($input);
        }

        $feedUrl = trim((string) ($context->config['feed_url'] ?? ''));
        $keywords = trim((string) ($context->config['keywords'] ?? ''));

        $context->log("خواندن آخرین مطلب خوراک RSS از $feedUrl");

        $result = (new RssFeedClient())->fetch(
            url: $feedUrl,
            limit: 5,
            keywords: $keywords,
        );

        if ($result['items'] === []) {
            throw NodeExecutionException::because(
                $keywords !== ''
                    ? 'هیچ مطلبی در این خوراک متناسب با کلمات کلیدی واردشده یافت نشد.'
                    : 'هیچ مطلبی در این خوراک RSS یافت نشد.'
            );
        }

        $latest = $result['items'][0];

        return NodeResult::of([
            ...$latest,
            'feed' => $result['feed'],
        ]);
    }
}
