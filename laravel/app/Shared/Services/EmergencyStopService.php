<?php

namespace App\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmergencyStopService
{
    public const CACHE_KEY = 'jobpilot_emergency_stop_active';
    public const METADATA_KEY = 'jobpilot_emergency_stop_metadata';

    /**
     * Check if global emergency stop is currently engaged.
     */
    public function isStopped(): bool
    {
        if (env('EMERGENCY_STOP', false) === true) {
            return true;
        }

        return (bool) Cache::get(self::CACHE_KEY, false);
    }

    /**
     * Alias for isStopped()
     */
    public function isEngaged(): bool
    {
        return $this->isStopped();
    }

    /**
     * Activate the emergency kill switch.
     */
    public function trigger(string $reason = 'Manual administrative kill switch engaged', ?int $userId = null): void
    {
        Cache::forever(self::CACHE_KEY, true);
        Cache::forever(self::METADATA_KEY, [
            'engaged_at' => now()->toIso8601String(),
            'engaged_by_user_id' => $userId,
            'reason' => $reason,
        ]);

        Log::emergency("EMERGENCY STOP ENGAGED: {$reason} (User ID: " . ($userId ?? 'system') . ")");
    }

    /**
     * Alias for trigger()
     */
    public function engage(string $reason = 'Manual administrative kill switch engaged', ?int $userId = null): void
    {
        $this->trigger($reason, $userId);
    }

    /**
     * Resume normal application operations.
     */
    public function resume(?int $userId = null): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::METADATA_KEY);

        Log::info("EMERGENCY STOP CLEARED: Operations resumed by User ID: " . ($userId ?? 'system'));
    }

    /**
     * Get emergency stop status information.
     */
    public function getStatus(): array
    {
        $isEngaged = $this->isStopped();
        $meta = Cache::get(self::METADATA_KEY, []);

        return [
            'is_engaged' => $isEngaged,
            'engaged_at' => $meta['engaged_at'] ?? null,
            'reason' => $meta['reason'] ?? ($isEngaged ? 'Triggered via environment setting' : 'System nominal'),
            'engaged_by' => $meta['engaged_by_user_id'] ?? null,
        ];
    }
}

