<?php

namespace App\Modules\Applications\app\Services;

use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationIdentityKey;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use Carbon\Carbon;

class DuplicateCheckerService
{
    public const RESULT_NEW = 'NEW_VACANCY';
    public const RESULT_EXACT_DUPLICATE = 'EXACT_DUPLICATE';
    public const RESULT_REOPENED_ELIGIBLE = 'REOPENED_AFTER_60_DAYS';
    public const RESULT_ALREADY_STAGED = 'ALREADY_STAGED';

    public function __construct(
        protected FingerprintService $fingerprintService
    ) {}

    /**
     * Check if a candidate has previously applied or registered this vacancy.
     */
    public function check(
        int $userId,
        string $companyName,
        string $jobTitle,
        ?string $location = null,
        ?string $externalReqId = null,
        ?string $applyUrl = null
    ): array {
        $fingerprint = $this->fingerprintService->generate(
            $companyName,
            $jobTitle,
            $location,
            $externalReqId,
            $applyUrl
        );

        // 1. Direct fingerprint match in registry
        $existing = ApplicationRegistry::with(['applications.roleProfile', 'identityKeys'])
            ->where('user_id', $userId)
            ->where('canonical_fingerprint', $fingerprint)
            ->first();

        // 2. Identity key cross-platform lookup if req ID or apply URL exists
        if (!$existing && (!empty($externalReqId) || !empty($applyUrl))) {
            $existingKey = ApplicationIdentityKey::whereHas('registry', fn($q) => $q->where('user_id', $userId))
                ->where(function ($q) use ($externalReqId, $applyUrl) {
                    if (!empty($externalReqId)) {
                        $q->orWhere(function ($sub) use ($externalReqId) {
                            $sub->where('key_type', 'REQ_ID')->where('key_value', $externalReqId);
                        });
                    }
                    if (!empty($applyUrl)) {
                        $normUrl = $this->fingerprintService->normalizeUrl($applyUrl);
                        $q->orWhere(function ($sub) use ($normUrl) {
                            $sub->where('key_type', 'APPLY_URL')->where('key_value', $normUrl);
                        });
                    }
                })
                ->first();

            if ($existingKey) {
                $existing = $existingKey->registry;
            }
        }

        if (!$existing) {
            return [
                'status' => self::RESULT_NEW,
                'is_duplicate' => false,
                'can_apply' => true,
                'fingerprint' => $fingerprint,
                'message' => 'No prior application history found. Vacancy is fresh and eligible for submission.',
                'registry' => null,
            ];
        }

        $latestApplication = $existing->applications()->latest()->first();
        $lastAppliedDate = $existing->last_applied_at ?? ($latestApplication?->sent_at ?? $latestApplication?->created_at);

        if ($lastAppliedDate) {
            $daysSinceApplication = Carbon::parse($lastAppliedDate)->diffInDays(now());

            if ($daysSinceApplication >= 60) {
                return [
                    'status' => self::RESULT_REOPENED_ELIGIBLE,
                    'is_duplicate' => true,
                    'can_apply' => true,
                    'fingerprint' => $fingerprint,
                    'days_ago' => $daysSinceApplication,
                    'last_applied_at' => $lastAppliedDate,
                    'message' => "Application was previously submitted {$daysSinceApplication} days ago. Since 60+ days have passed, you may re-apply with an updated profile.",
                    'registry' => $existing,
                    'latest_application' => $latestApplication,
                ];
            }

            return [
                'status' => self::RESULT_EXACT_DUPLICATE,
                'is_duplicate' => true,
                'can_apply' => false,
                'fingerprint' => $fingerprint,
                'days_ago' => $daysSinceApplication,
                'last_applied_at' => $lastAppliedDate,
                'message' => "Duplicate detected! You applied for this vacancy {$daysSinceApplication} days ago on " . Carbon::parse($lastAppliedDate)->toFormattedDateString() . ". Re-application is blocked within the 60-day window to protect sender reputation.",
                'registry' => $existing,
                'latest_application' => $latestApplication,
            ];
        }

        return [
            'status' => self::RESULT_ALREADY_STAGED,
            'is_duplicate' => true,
            'can_apply' => true,
            'fingerprint' => $fingerprint,
            'message' => "Vacancy is already recorded in your pipeline with status '{$existing->status}'.",
            'registry' => $existing,
            'latest_application' => $latestApplication,
        ];
    }
}

