<?php

namespace App\Shared\Contracts;

interface FingerprintableInterface
{
    /**
     * Get the array of canonical attributes used to compute the unique vacancy or application fingerprint.
     */
    public function getFingerprintComponents(): array;

    /**
     * Compute and return the SHA-256 canonical fingerprint string.
     */
    public function generateFingerprint(): string;
}

