<?php

namespace App\Admin;

use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

final class GscReportExporter
{
    public function __construct(
        private readonly GoogleSearchConsoleClient $client,
    ) {}

    public function generateMarkdown(int $days = 28): array
    {
        $snapshot = $this->client->fetchSnapshot($days);
        $meta = $snapshot['meta'];
        $nowTehran = Carbon::now('Asia/Tehran');
        $jNow = Jalalian::fromCarbon($nowTehran);

        $curTotals = $this->normalizeRow($snapshot['current_totals']);
        $prvTotals = $this->normalizeRow($snapshot['previous_totals']);
        $imgTotals = $this->normalizeRow($snapshot['image_totals']);

        $currentQueries = $this->indexByKey($snapshot['current_queries']);
        $previousQueries = $this->indexByKey($snapshot['previous_queries']);
        $currentPages = $this->indexByKey($snapshot['current_pages']);
        $previousPages = $this->indexByKey($snapshot['previous_pages']);

        $queryPages = array_map(function (array $row): array {
            $norm = $this->normalizeRow($row);
            $norm['query'] = (string) ($row['keys'][0] ?? '');
            $norm['page'] = (string) ($row['keys'][1] ?? '');

            return $norm;
        }, $snapshot['query_pages']);

        $topPageByQuery = [];
        $pagesByQuery = [];

        foreach ($queryPages as $pair) {
            $q = $pair['query'];
            if ($q === '') {
                continue;
            }

            if (! isset($topPageByQuery[$q]) || $pair['impressions'] > $topPageByQuery[$q]['impressions']) {
                $topPageByQuery[$q] = $pair;
            }

            $pagesByQuery[$q][] = $pair;
        }

        $lines = [];
        $lines[] = '---';
        $lines[] = 'report_type: google_search_console_seo_snapshot';
        $lines[] = 'platform: Tameshk Flow';
        $lines[] = 'property: ' . $meta['site_url'];
        $lines[] = 'period_days: ' . $meta['days'];
        $lines[] = 'current_period: ' . $meta['current_start'] . ' to ' . $meta['current_end'];
        $lines[] = 'previous_period: ' . $meta['previous_start'] . ' to ' . $meta['previous_end'];
        $lines[] = 'generated_at_utc: ' . Carbon::now('UTC')->toIso8601String();
        $lines[] = 'generated_at_tehran: ' . $nowTehran->format('Y-m-d H:i:s') . ' (' . $jNow->format('Y/m/d H:i') . ')';
        $lines[] = 'data_state: all';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '# Google Search Console Live SEO Report — Tameshk Flow';
        $lines[] = '';
        $lines[] = '## 1. Executive Summary (Current vs Previous ' . $meta['days'] . ' Days)';
        $lines[] = '';
        $lines[] = '| Metric | Current Period | Previous Period | Delta | Change |';
        $lines[] = '| --- | ---: | ---: | ---: | ---: |';
        $lines[] = sprintf(
            '| Web Clicks | %s | %s | %s | %s |',
            number_format($curTotals['clicks']),
            number_format($prvTotals['clicks']),
            $this->signedInt($curTotals['clicks'] - $prvTotals['clicks']),
            $this->percentChange($curTotals['clicks'], $prvTotals['clicks'])
        );
        $lines[] = sprintf(
            '| Web Impressions | %s | %s | %s | %s |',
            number_format($curTotals['impressions']),
            number_format($prvTotals['impressions']),
            $this->signedInt($curTotals['impressions'] - $prvTotals['impressions']),
            $this->percentChange($curTotals['impressions'], $prvTotals['impressions'])
        );
        $lines[] = sprintf(
            '| Average CTR | %s%% | %s%% | %s pp | %s |',
            $this->fmtFloat($curTotals['ctr'] * 100, 2),
            $this->fmtFloat($prvTotals['ctr'] * 100, 2),
            $this->signedFloat(($curTotals['ctr'] - $prvTotals['ctr']) * 100, 2),
            $this->percentChange($curTotals['ctr'], $prvTotals['ctr'])
        );
        $lines[] = sprintf(
            '| Average Position | %s | %s | %s | %s |',
            $this->fmtFloat($curTotals['position'], 2),
            $this->fmtFloat($prvTotals['position'], 2),
            $this->positionDelta($curTotals['position'], $prvTotals['position']),
            $curTotals['position'] > 0 && $prvTotals['position'] > 0
                ? ($curTotals['position'] < $prvTotals['position'] ? 'Improved' : ($curTotals['position'] > $prvTotals['position'] ? 'Declined' : 'Flat'))
                : 'N/A'
        );
        $lines[] = sprintf(
            '| Image Search Clicks / Impressions | %s / %s | - | - | CTR: %s%%, Pos: %s |',
            number_format($imgTotals['clicks']),
            number_format($imgTotals['impressions']),
            $this->fmtFloat($imgTotals['ctr'] * 100, 2),
            $this->fmtFloat($imgTotals['position'], 2)
        );
        $lines[] = '';
        $lines[] = '## 2. Sitemaps & Indexing Health';
        $lines[] = '';

        if ($snapshot['sitemaps'] === []) {
            $lines[] = '_No sitemaps reported for this property._';
        } else {
            $lines[] = '| Sitemap URL | Type | Submitted | Indexed | Index Ratio | Warnings | Errors | Last Downloaded |';
            $lines[] = '| --- | --- | ---: | ---: | ---: | ---: | ---: | --- |';

            foreach ($snapshot['sitemaps'] as $sm) {
                $path = $this->cleanPath((string) ($sm['path'] ?? ''));
                $warnings = (int) ($sm['warnings'] ?? 0);
                $errors = (int) ($sm['errors'] ?? 0);
                $lastDownloaded = (string) ($sm['lastDownloaded'] ?? '-');
                $contents = is_array($sm['contents'] ?? null) ? $sm['contents'] : [];

                if ($contents === []) {
                    $lines[] = sprintf(
                        '| %s | %s | 0 | 0 | 0%% | %d | %d | %s |',
                        $this->esc($path),
                        $this->esc((string) ($sm['type'] ?? 'sitemap')),
                        $warnings,
                        $errors,
                        $this->esc($lastDownloaded)
                    );
                    continue;
                }

                foreach ($contents as $content) {
                    $submitted = (int) ($content['submitted'] ?? 0);
                    $indexed = (int) ($content['indexed'] ?? 0);
                    $ratio = $submitted > 0 ? round(($indexed / $submitted) * 100, 1) . '%' : 'N/A';

                    $lines[] = sprintf(
                        '| %s | %s | %d | %d | %s | %d | %d | %s |',
                        $this->esc($path),
                        $this->esc((string) ($content['type'] ?? 'web')),
                        $submitted,
                        $indexed,
                        $ratio,
                        $warnings,
                        $errors,
                        $this->esc($lastDownloaded)
                    );
                }
            }
        }

        $lines[] = '';
        $lines[] = '## 3. Automated SEO Opportunity Signals (Pre-Computed for AI Decision Making)';
        $lines[] = '';

        $striking = array_values(array_filter(
            $currentQueries,
            fn (array $row) => $row['position'] >= 4.0 && $row['position'] <= 20.0 && $row['impressions'] >= 5
        ));
        usort($striking, fn (array $a, array $b) => $b['impressions'] <=> $a['impressions']);
        $striking = array_slice($striking, 0, 40);

        $lines[] = '### 3.1 Striking Distance Keywords (Position 4–20, Highest Impression Upside)';
        $lines[] = '';
        if ($striking === []) {
            $lines[] = '_No striking distance keywords found in this window._';
        } else {
            $lines[] = '| Query | Position | Impressions | Clicks | CTR | Top Ranking Page |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: | --- |';
            foreach ($striking as $row) {
                $q = $row['key'];
                $topPage = isset($topPageByQuery[$q]) ? $this->cleanPath($topPageByQuery[$q]['page']) : '-';
                $lines[] = sprintf(
                    '| %s | %s | %s | %s | %s%% | %s |',
                    $this->esc($q),
                    $this->fmtFloat($row['position'], 1),
                    number_format($row['impressions']),
                    number_format($row['clicks']),
                    $this->fmtFloat($row['ctr'] * 100, 2),
                    $this->esc($topPage)
                );
            }
        }

        $lines[] = '';
        $lowCtrPages = array_values(array_filter(
            $currentPages,
            fn (array $row) => $row['position'] <= 12.0 && $row['impressions'] >= 15 && $row['ctr'] < 0.04
        ));
        usort($lowCtrPages, fn (array $a, array $b) => $b['impressions'] <=> $a['impressions']);
        $lowCtrPages = array_slice($lowCtrPages, 0, 25);

        $lines[] = '### 3.2 High-Impression / Low-CTR Pages (Title & Meta Description Rewrite Targets)';
        $lines[] = '';
        if ($lowCtrPages === []) {
            $lines[] = '_No high-impression low-CTR pages detected in this window._';
        } else {
            $lines[] = '| Page Path | Impressions | Clicks | CTR | Avg Position |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: |';
            foreach ($lowCtrPages as $row) {
                $lines[] = sprintf(
                    '| %s | %s | %s | %s%% | %s |',
                    $this->esc($this->cleanPath($row['key'])),
                    number_format($row['impressions']),
                    number_format($row['clicks']),
                    $this->fmtFloat($row['ctr'] * 100, 2),
                    $this->fmtFloat($row['position'], 1)
                );
            }
        }

        $lines[] = '';
        $cannibalization = [];
        foreach ($pagesByQuery as $query => $pairs) {
            if (count($pairs) < 2) {
                continue;
            }
            $totalImp = array_sum(array_column($pairs, 'impressions'));
            if ($totalImp < 10) {
                continue;
            }
            usort($pairs, fn (array $a, array $b) => $b['impressions'] <=> $a['impressions']);
            $cannibalization[] = [
                'query' => $query,
                'total_impressions' => $totalImp,
                'total_clicks' => array_sum(array_column($pairs, 'clicks')),
                'pages' => array_slice($pairs, 0, 4),
            ];
        }
        usort($cannibalization, fn (array $a, array $b) => $b['total_impressions'] <=> $a['total_impressions']);
        $cannibalization = array_slice($cannibalization, 0, 25);

        $lines[] = '### 3.3 Keyword Cannibalization Candidates (Multiple URLs Ranking for Same Query)';
        $lines[] = '';
        if ($cannibalization === []) {
            $lines[] = '_No significant keyword cannibalization detected._';
        } else {
            $lines[] = '| Query | Total Imp | Total Clicks | Competing URLs (Path [Pos / Imp / Clicks]) |';
            $lines[] = '| --- | ---: | ---: | --- |';
            foreach ($cannibalization as $item) {
                $formattedUrls = array_map(fn (array $p) => sprintf(
                    '%s [pos:%s, imp:%d, clk:%d]',
                    $this->cleanPath($p['page']),
                    $this->fmtFloat($p['position'], 1),
                    $p['impressions'],
                    $p['clicks']
                ), $item['pages']);

                $lines[] = sprintf(
                    '| %s | %s | %s | %s |',
                    $this->esc($item['query']),
                    number_format($item['total_impressions']),
                    number_format($item['total_clicks']),
                    $this->esc(implode(' ; ', $formattedUrls))
                );
            }
        }

        $lines[] = '';
        [$pageWinners, $pageLosers] = $this->computeMovers($currentPages, $previousPages, 15);
        $lines[] = '### 3.4 Biggest Page Winners & Losers (vs Previous Period)';
        $lines[] = '';
        $lines[] = '| Direction | Page Path | Clicks (Cur / Prev / Δ) | Impressions (Cur / Prev / Δ) | Position (Cur / Prev / Δ) |';
        $lines[] = '| --- | --- | --- | --- | --- |';
        foreach ($pageWinners as $m) {
            $lines[] = sprintf(
                '| WINNER | %s | %d / %d (%s) | %d / %d (%s) | %s / %s (%s) |',
                $this->esc($this->cleanPath($m['key'])),
                $m['cur_clicks'],
                $m['prv_clicks'],
                $this->signedInt($m['click_diff']),
                $m['cur_impressions'],
                $m['prv_impressions'],
                $this->signedInt($m['imp_diff']),
                $this->fmtFloat($m['cur_pos'], 1),
                $this->fmtFloat($m['prv_pos'], 1),
                $this->positionDelta($m['cur_pos'], $m['prv_pos'])
            );
        }
        foreach ($pageLosers as $m) {
            $lines[] = sprintf(
                '| LOSER | %s | %d / %d (%s) | %d / %d (%s) | %s / %s (%s) |',
                $this->esc($this->cleanPath($m['key'])),
                $m['cur_clicks'],
                $m['prv_clicks'],
                $this->signedInt($m['click_diff']),
                $m['cur_impressions'],
                $m['prv_impressions'],
                $this->signedInt($m['imp_diff']),
                $this->fmtFloat($m['cur_pos'], 1),
                $this->fmtFloat($m['prv_pos'], 1),
                $this->positionDelta($m['cur_pos'], $m['prv_pos'])
            );
        }

        $lines[] = '';
        $newQueries = array_values(array_filter(
            $currentQueries,
            fn (array $row) => ! isset($previousQueries[$row['key']])
        ));
        usort($newQueries, fn (array $a, array $b) => $b['impressions'] <=> $a['impressions']);
        $newQueries = array_slice($newQueries, 0, 25);

        $lines[] = '### 3.5 Newly Discovered Queries (0 Impressions in Previous Period)';
        $lines[] = '';
        if ($newQueries === []) {
            $lines[] = '_No new emerging queries found._';
        } else {
            $lines[] = '| Query | Impressions | Clicks | CTR | Position |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: |';
            foreach ($newQueries as $row) {
                $lines[] = sprintf(
                    '| %s | %s | %s | %s%% | %s |',
                    $this->esc($row['key']),
                    number_format($row['impressions']),
                    number_format($row['clicks']),
                    $this->fmtFloat($row['ctr'] * 100, 2),
                    $this->fmtFloat($row['position'], 1)
                );
            }
        }

        $lines[] = '';
        $lines[] = '## 4. Top Pages Performance (with Period-over-Period Comparison)';
        $lines[] = '';
        if ($currentPages === []) {
            $lines[] = '_No page data available for this period._';
        } else {
            $lines[] = '| # | Page Path | Clicks | Δ Clicks | Impressions | Δ Imp | CTR | Position | Δ Pos |';
            $lines[] = '| ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |';
            $rank = 0;
            foreach (array_slice(array_values($currentPages), 0, 200) as $row) {
                $rank++;
                $prev = $previousPages[$row['key']] ?? null;
                $prevClicks = $prev['clicks'] ?? 0;
                $prevImp = $prev['impressions'] ?? 0;
                $prevPos = $prev['position'] ?? 0.0;

                $lines[] = sprintf(
                    '| %d | %s | %s | %s | %s | %s | %s%% | %s | %s |',
                    $rank,
                    $this->esc($this->cleanPath($row['key'])),
                    number_format($row['clicks']),
                    $this->signedInt($row['clicks'] - $prevClicks),
                    number_format($row['impressions']),
                    $this->signedInt($row['impressions'] - $prevImp),
                    $this->fmtFloat($row['ctr'] * 100, 2),
                    $this->fmtFloat($row['position'], 1),
                    $prev ? $this->positionDelta($row['position'], $prevPos) : 'NEW'
                );
            }
        }

        $lines[] = '';
        $lines[] = '## 5. Top Search Queries (with Period-over-Period Comparison)';
        $lines[] = '';
        if ($currentQueries === []) {
            $lines[] = '_No query data available for this period._';
        } else {
            $lines[] = '| # | Query | Clicks | Δ Clicks | Impressions | Δ Imp | CTR | Position | Δ Pos |';
            $lines[] = '| ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |';
            $rank = 0;
            foreach (array_slice(array_values($currentQueries), 0, 250) as $row) {
                $rank++;
                $prev = $previousQueries[$row['key']] ?? null;
                $prevClicks = $prev['clicks'] ?? 0;
                $prevImp = $prev['impressions'] ?? 0;
                $prevPos = $prev['position'] ?? 0.0;

                $lines[] = sprintf(
                    '| %d | %s | %s | %s | %s | %s | %s%% | %s | %s |',
                    $rank,
                    $this->esc($row['key']),
                    number_format($row['clicks']),
                    $this->signedInt($row['clicks'] - $prevClicks),
                    number_format($row['impressions']),
                    $this->signedInt($row['impressions'] - $prevImp),
                    $this->fmtFloat($row['ctr'] * 100, 2),
                    $this->fmtFloat($row['position'], 1),
                    $prev ? $this->positionDelta($row['position'], $prevPos) : 'NEW'
                );
            }
        }

        $lines[] = '';
        $lines[] = '## 6. Query-to-Page Matrix (Top 250 Pairs)';
        $lines[] = '';
        if ($queryPages === []) {
            $lines[] = '_No query-page combinations available._';
        } else {
            $lines[] = '| Query | Page Path | Clicks | Impressions | CTR | Position |';
            $lines[] = '| --- | --- | ---: | ---: | ---: | ---: |';
            foreach (array_slice($queryPages, 0, 250) as $pair) {
                $lines[] = sprintf(
                    '| %s | %s | %s | %s | %s%% | %s |',
                    $this->esc($pair['query']),
                    $this->esc($this->cleanPath($pair['page'])),
                    number_format($pair['clicks']),
                    number_format($pair['impressions']),
                    $this->fmtFloat($pair['ctr'] * 100, 2),
                    $this->fmtFloat($pair['position'], 1)
                );
            }
        }

        $lines[] = '';
        $lines[] = '## 7. Device, Country & Search Appearance Breakdown';
        $lines[] = '';
        $lines[] = '### 7.1 Devices';
        $lines[] = '';
        if ($snapshot['devices'] === []) {
            $lines[] = '_No device breakdown available._';
        } else {
            $totalDeviceClicks = max(1, array_sum(array_map(fn ($r) => (int) ($r['clicks'] ?? 0), $snapshot['devices'])));
            $lines[] = '| Device | Clicks | Click Share | Impressions | CTR | Avg Position |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: | ---: |';
            foreach ($snapshot['devices'] as $r) {
                $n = $this->normalizeRow($r);
                $share = round(($n['clicks'] / $totalDeviceClicks) * 100, 1);
                $lines[] = sprintf(
                    '| %s | %s | %s%% | %s | %s%% | %s |',
                    $this->esc((string) ($r['keys'][0] ?? 'UNKNOWN')),
                    number_format($n['clicks']),
                    $this->fmtFloat($share, 1),
                    number_format($n['impressions']),
                    $this->fmtFloat($n['ctr'] * 100, 2),
                    $this->fmtFloat($n['position'], 1)
                );
            }
        }

        $lines[] = '';
        $lines[] = '### 7.2 Top Countries';
        $lines[] = '';
        if ($snapshot['countries'] === []) {
            $lines[] = '_No country breakdown available._';
        } else {
            $lines[] = '| Country (ISO-3) | Clicks | Impressions | CTR | Avg Position |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: |';
            foreach ($snapshot['countries'] as $r) {
                $n = $this->normalizeRow($r);
                $lines[] = sprintf(
                    '| %s | %s | %s | %s%% | %s |',
                    strtoupper($this->esc((string) ($r['keys'][0] ?? 'UNK'))),
                    number_format($n['clicks']),
                    number_format($n['impressions']),
                    $this->fmtFloat($n['ctr'] * 100, 2),
                    $this->fmtFloat($n['position'], 1)
                );
            }
        }

        $lines[] = '';
        $lines[] = '### 7.3 Search Appearances (Rich Results)';
        $lines[] = '';
        if ($snapshot['appearances'] === []) {
            $lines[] = '_No special search appearance data recorded in this period._';
        } else {
            $lines[] = '| Search Appearance | Clicks | Impressions | CTR | Avg Position |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: |';
            foreach ($snapshot['appearances'] as $r) {
                $n = $this->normalizeRow($r);
                $lines[] = sprintf(
                    '| %s | %s | %s | %s%% | %s |',
                    $this->esc((string) ($r['keys'][0] ?? '-')),
                    number_format($n['clicks']),
                    number_format($n['impressions']),
                    $this->fmtFloat($n['ctr'] * 100, 2),
                    $this->fmtFloat($n['position'], 1)
                );
            }
        }

        $lines[] = '';
        $lines[] = '## 8. Daily Performance Trend';
        $lines[] = '';
        if ($snapshot['daily'] === []) {
            $lines[] = '_No daily trend data available._';
        } else {
            $dailyRows = $snapshot['daily'];
            usort($dailyRows, fn (array $a, array $b) => strcmp((string) ($a['keys'][0] ?? ''), (string) ($b['keys'][0] ?? '')));
            $lines[] = '| Date | Clicks | Impressions | CTR | Avg Position |';
            $lines[] = '| --- | ---: | ---: | ---: | ---: |';
            foreach ($dailyRows as $r) {
                $n = $this->normalizeRow($r);
                $lines[] = sprintf(
                    '| %s | %s | %s | %s%% | %s |',
                    $this->esc((string) ($r['keys'][0] ?? '-')),
                    number_format($n['clicks']),
                    number_format($n['impressions']),
                    $this->fmtFloat($n['ctr'] * 100, 2),
                    $this->fmtFloat($n['position'], 1)
                );
            }
        }

        $lines[] = '';
        $lines[] = '## 9. AI Agent Analysis Guide';
        $lines[] = '';
        $lines[] = '- **Site Context:** Tameshk Flow (`tameshkflow.ir`) is a Persian cloud workflow automation and AI Agent builder platform (Iranian no-code n8n alternative & Telegram bot builder).';
        $lines[] = '- **Position Delta Legend:** Positive `Δ Pos` (`+X.X`) means rank improved (moved closer to #1); negative (`-X.X`) means rank dropped.';
        $lines[] = '- **Recommended Action Areas:** Prioritize Section 3.1 (striking distance keywords) for content & internal linking upgrades, Section 3.2 for Title/Meta CTR rewrites, Section 3.3 for canonical/consolidation fixes, Section 3.4 for diagnosing traffic drops, and Section 2 for sitemap indexation gaps.';
        $lines[] = '';

        $filename = sprintf(
            'tameshkflow-gsc-seo-report-%dd-%s.md',
            $meta['days'],
            $nowTehran->format('Y-m-d')
        );

        return [
            'filename' => $filename,
            'content' => implode("\n", $lines),
        ];
    }

    private function normalizeRow(?array $row): array
    {
        return [
            'clicks' => (int) round((float) ($row['clicks'] ?? 0)),
            'impressions' => (int) round((float) ($row['impressions'] ?? 0)),
            'ctr' => (float) ($row['ctr'] ?? 0.0),
            'position' => (float) ($row['position'] ?? 0.0),
        ];
    }

    private function indexByKey(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $key = (string) ($row['keys'][0] ?? '');
            if ($key === '') {
                continue;
            }

            $indexed[$key] = [
                'key' => $key,
                ...$this->normalizeRow($row),
            ];
        }

        return $indexed;
    }

    private function computeMovers(array $current, array $previous, int $limit): array
    {
        $allKeys = array_unique(array_merge(array_keys($current), array_keys($previous)));
        $movers = [];

        foreach ($allKeys as $key) {
            $c = $current[$key] ?? ['clicks' => 0, 'impressions' => 0, 'position' => 0.0];
            $p = $previous[$key] ?? ['clicks' => 0, 'impressions' => 0, 'position' => 0.0];

            $clickDiff = $c['clicks'] - $p['clicks'];
            $impDiff = $c['impressions'] - $p['impressions'];

            if ($clickDiff === 0 && abs($impDiff) < 10) {
                continue;
            }

            $movers[] = [
                'key' => $key,
                'cur_clicks' => $c['clicks'],
                'prv_clicks' => $p['clicks'],
                'click_diff' => $clickDiff,
                'cur_impressions' => $c['impressions'],
                'prv_impressions' => $p['impressions'],
                'imp_diff' => $impDiff,
                'cur_pos' => $c['position'],
                'prv_pos' => $p['position'],
            ];
        }

        $winners = array_values(array_filter($movers, fn (array $m) => $m['click_diff'] > 0 || ($m['click_diff'] === 0 && $m['imp_diff'] > 0)));
        usort($winners, fn (array $a, array $b) => ($b['click_diff'] <=> $a['click_diff']) ?: ($b['imp_diff'] <=> $a['imp_diff']));

        $losers = array_values(array_filter($movers, fn (array $m) => $m['click_diff'] < 0 || ($m['click_diff'] === 0 && $m['imp_diff'] < 0)));
        usort($losers, fn (array $a, array $b) => ($a['click_diff'] <=> $b['click_diff']) ?: ($a['imp_diff'] <=> $b['imp_diff']));

        return [
            array_slice($winners, 0, $limit),
            array_slice($losers, 0, $limit),
        ];
    }

    private function cleanPath(string $url): string
    {
        $decoded = rawurldecode(trim($url));
        $parsed = parse_url($decoded);

        if (! is_array($parsed) || empty($parsed['path'])) {
            return $decoded !== '' ? $decoded : '/';
        }

        $path = $parsed['path'];
        if (! empty($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }

        return $path;
    }

    private function esc(string $text): string
    {
        $clean = str_replace(["\r\n", "\r", "\n"], ' ', trim($text));

        return str_replace('|', '\|', $clean);
    }

    private function fmtFloat(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    private function signedInt(int $value): string
    {
        return ($value > 0 ? '+' : '') . number_format($value);
    }

    private function signedFloat(float $value, int $decimals): string
    {
        return ($value > 0 ? '+' : '') . $this->fmtFloat($value, $decimals);
    }

    private function percentChange(float $current, float $previous): string
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? '+100.0%' : '0.0%';
        }

        $pct = (($current - $previous) / $previous) * 100;

        return $this->signedFloat($pct, 1) . '%';
    }

    private function positionDelta(float $currentPos, float $previousPos): string
    {
        if ($currentPos <= 0.0 || $previousPos <= 0.0) {
            return '-';
        }

        $improvement = $previousPos - $currentPos;

        return $this->signedFloat($improvement, 1);
    }
}
