<?php

namespace App\Console\Commands;

use App\Docs\DocumentationRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate a fresh sitemap.xml and robots.txt for public indexable pages.';

    public function handle(): int
    {
        $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

        $urls = [];

        $urls[] = [
            'loc' => $baseUrl . '/',
            'lastmod' => $this-> resolveLastModified([
                'views/pages/home.blade.php',
                'views/components/pages/home/hero.blade.php',
                'views/components/pages/home/features.blade.php',
                'views/components/pages/home/pricing.blade.php',
            ]),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        foreach (DocumentationRepository::allArticles() as $index => $article) {
            $viewRelativePath = 'views/' . str_replace('.', '/', $article['view']) . '.blade.php';

            $urls[] = [
                'loc' => $baseUrl . '/docs/' . $article['slug'],
                'lastmod' => $this->resolveLastModified([
                    $viewRelativePath,
                    'views/pages/docs/show.blade.php',
                ]),
                'changefreq' => 'weekly',
                'priority' => $index === 0 ? '0.9' : '0.8',
            ];
        }

        $urls[] = [
            'loc' => $baseUrl . '/terms',
            'lastmod' => $this->resolveLastModified([
                'views/pages/legal/terms.blade.php',
                'views/components/legal/layout.blade.php',
            ]),
            'changefreq' => 'monthly',
            'priority' => '0.5',
        ];

        $urls[] = [
            'loc' => $baseUrl . '/privacy',
            'lastmod' => $this->resolveLastModified([
                'views/pages/legal/privacy.blade.php',
                'views/components/legal/layout.blade.php',
            ]),
            'changefreq' => 'monthly',
            'priority' => '0.5',
        ];

        $sitemapPath = public_path('sitemap.xml');

        if (File::exists($sitemapPath)) {
            File::delete($sitemapPath);
        }

        File::put($sitemapPath, $this->buildSitemapXml($urls));

        $robotsPath = public_path('robots.txt');

        if (File::exists($robotsPath)) {
            File::delete($robotsPath);
        }

        File::put($robotsPath, $this->buildRobotsTxt($baseUrl));

        $this->info('✅ Sitemap and robots.txt generated successfully (' . count($urls) . ' URLs).');

        return self::SUCCESS;
    }

    private function resolveLastModified(array $resourceRelativePaths): string
    {
        $latestTimestamp = 0;

        foreach ($resourceRelativePaths as $relativePath) {
            $fullPath = resource_path($relativePath);

            if (File::exists($fullPath)) {
                $mtime = File::lastModified($fullPath);
                if ($mtime > $latestTimestamp) {
                    $latestTimestamp = $mtime;
                }
            }
        }

        $date = $latestTimestamp > 0
            ? Carbon::createFromTimestamp($latestTimestamp, config('app.timezone', 'Asia/Tehran'))
            : now();

        return $date->toAtomString();
    }

    private function buildSitemapXml(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $item) {
            $loc = htmlspecialchars($item['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $lastmod = htmlspecialchars($item['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $changefreq = htmlspecialchars($item['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $priority = htmlspecialchars($item['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8');

            $lines[] = '    <url>';
            $lines[] = "        <loc>$loc</loc>";
            $lines[] = "        <lastmod>$lastmod</lastmod>";
            $lines[] = "        <changefreq>$changefreq</changefreq>";
            $lines[] = "        <priority>$priority</priority>";
            $lines[] = '    </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    private function buildRobotsTxt(string $baseUrl): string
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /docs',
            'Allow: /docs/',
            'Allow: /terms',
            'Allow: /privacy',
            '',
            'Disallow: /app/',
            'Disallow: /terminal',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /auth/',
            'Disallow: /oauth/',
            'Disallow: /billing/',
            'Disallow: /webhook/',
            'Disallow: /webhook-test/',
            'Disallow: /livewire/',
            '',
            "Sitemap: $baseUrl/sitemap.xml",
        ];

        return implode("\n", $lines) . "\n";
    }
}
