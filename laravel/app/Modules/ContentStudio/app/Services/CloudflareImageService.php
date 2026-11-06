<?php

namespace App\Modules\ContentStudio\app\Services;

use App\Models\User;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CloudflareImageService
{
    /**
     * Generate visual banner using Cloudflare Workers AI or local SVG/PNG engine.
     *
     * @return array{path: string, url: string, provider: string}
     */
    public function generateBanner(User $user, string $prompt, string $title = 'System Architecture'): array
    {
        $dir = storage_path("app/public/content_images/{$user->id}");
        File::ensureDirectoryExists($dir);

        // 1. Check for authorized Cloudflare Workers AI credentials
        $credential = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'cloudflare_workers_ai')
            ->where('is_active', true)
            ->first();

        $accountId = $credential?->settings['account_id'] ?? null;
        $apiToken = $credential?->api_key;

        if ($accountId && $apiToken) {
            try {
                $response = Http::withToken($apiToken)
                    ->timeout(20)
                    ->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/@cf/stabilityai/stable-diffusion-xl-base-1.0", [
                        'prompt' => $prompt,
                        'num_steps' => 20,
                    ]);

                if ($response->successful()) {
                    $fileName = 'cf_ai_' . time() . '_' . Str::random(6) . '.png';
                    $fullPath = "{$dir}/{$fileName}";
                    File::put($fullPath, $response->body());

                    $relPath = "content_images/{$user->id}/{$fileName}";
                    return [
                        'path' => $relPath,
                        'url' => Storage::url($relPath),
                        'provider' => 'cloudflare_workers_ai',
                    ];
                }
            } catch (Throwable $e) {
                // Gracefully fallback to deterministic local banner generator
            }
        }

        // 2. Deterministic High-Quality Local SVG Banner Generator
        return $this->generateLocalBanner($user, $title, $prompt);
    }

    /**
     * Generate local high-resolution developer promotional SVG banner.
     *
     * @return array{path: string, url: string, provider: string}
     */
    public function generateLocalBanner(User $user, string $title, string $prompt = ''): array
    {
        $dir = storage_path("app/public/content_images/{$user->id}");
        File::ensureDirectoryExists($dir);

        $fileName = 'banner_' . time() . '_' . Str::random(6) . '.svg';
        $fullPath = "{$dir}/{$fileName}";

        $safeTitle = htmlspecialchars(Str::limit($title, 65), ENT_QUOTES, 'UTF-8');
        $safeUser = htmlspecialchars($user->name ?: 'Senior Engineer', ENT_QUOTES, 'UTF-8');

        // Extract key technology keywords for decorative badges
        $badges = ['PHP 8.4', 'Laravel', 'System Design', 'High Concurrency'];
        if (preg_match('/(mysql|tidb|database|sql)/i', $title)) {
            $badges = ['MySQL / TiDB', 'Database Indexing', 'High Throughput', 'Query Optimization'];
        } elseif (preg_match('/(redis|cache|caching)/i', $title)) {
            $badges = ['Redis', 'In-Memory Cache', 'Low Latency', 'Microservices'];
        } elseif (preg_match('/(microservice|api|distributed)/i', $title)) {
            $badges = ['REST APIs', 'Microservices', 'Distributed Systems', 'Docker'];
        }

        $badgeSvg = '';
        $bx = 80;
        foreach ($badges as $badge) {
            $w = (mb_strlen($badge) * 9) + 24;
            $badgeSvg .= "<rect x='{$bx}' y='380' width='{$w}' height='36' rx='18' fill='#1e293b' stroke='#38bdf8' stroke-width='1.5' />";
            $badgeSvg .= "<text x='" . ($bx + ($w / 2)) . "' y='403' fill='#38bdf8' font-family='Segoe UI, -apple-system, sans-serif' font-size='14' font-weight='600' text-anchor='middle'>{$badge}</text>";
            $bx += $w + 14;
        }

        $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
    <defs>
        <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#0b0f19" />
            <stop offset="50%" stop-color="#0f172a" />
            <stop offset="100%" stop-color="#1e1b4b" />
        </linearGradient>
        <linearGradient id="textGrad" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#38bdf8" />
            <stop offset="50%" stop-color="#818cf8" />
            <stop offset="100%" stop-color="#c084fc" />
        </linearGradient>
        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="15" result="blur" />
            <feComposite in="SourceGraphic" in2="blur" operator="over" />
        </filter>
    </defs>

    <!-- Background Canvas -->
    <rect width="1200" height="630" fill="url(#bgGrad)" />

    <!-- Grid Lines Pattern -->
    <g opacity="0.08" stroke="#ffffff" stroke-width="1">
        <line x1="0" y1="105" x2="1200" y2="105" />
        <line x1="0" y1="210" x2="1200" y2="210" />
        <line x1="0" y1="315" x2="1200" y2="315" />
        <line x1="0" y1="420" x2="1200" y2="420" />
        <line x1="0" y1="525" x2="1200" y2="525" />
        <line x1="200" y1="0" x2="200" y2="630" />
        <line x1="400" y1="0" x2="400" y2="630" />
        <line x1="600" y1="0" x2="600" y2="630" />
        <line x1="800" y1="0" x2="800" y2="630" />
        <line x1="1000" y1="0" x2="1000" y2="630" />
    </g>

    <!-- Top Branding / Tag -->
    <rect x="80" y="70" width="220" height="36" rx="8" fill="#1e293b" stroke="#334155" stroke-width="1" />
    <circle cx="102" cy="88" r="6" fill="#10b981" />
    <text x="120" y="94" fill="#94a3b8" font-family="Segoe UI, -apple-system, sans-serif" font-size="14" font-weight="700" letter-spacing="1">JOBPILOT AI • STUDIO</text>

    <!-- Main Title -->
    <text x="80" y="220" fill="#ffffff" font-family="Segoe UI, -apple-system, sans-serif" font-size="46" font-weight="800" line-height="1.2">
        {$safeTitle}
    </text>

    <!-- Subtitle / Hook -->
    <text x="80" y="295" fill="url(#textGrad)" font-family="Segoe UI, -apple-system, sans-serif" font-size="24" font-weight="600">
        Architecture Deep Dive &amp; Engineering Craftsmanship
    </text>

    <!-- Tech Stack Badges -->
    {$badgeSvg}

    <!-- Bottom Author Bar -->
    <line x1="80" y1="480" x2="1120" y2="480" stroke="#334155" stroke-width="1" opacity="0.6" />
    <text x="80" y="535" fill="#f8fafc" font-family="Segoe UI, -apple-system, sans-serif" font-size="20" font-weight="700">{$safeUser}</text>
    <text x="80" y="565" fill="#64748b" font-family="Segoe UI, -apple-system, sans-serif" font-size="15" font-weight="500">Software Engineer | Production Architecture Insights</text>

    <text x="1120" y="550" fill="#64748b" font-family="Segoe UI, -apple-system, sans-serif" font-size="16" font-weight="600" text-anchor="end">linkedin.com</text>
</svg>
SVG;

        File::put($fullPath, $svgContent);

        $relPath = "content_images/{$user->id}/{$fileName}";
        return [
            'path' => $relPath,
            'url' => Storage::url($relPath),
            'provider' => 'local_banner_generator',
        ];
    }
}

