<?php

namespace App\Modules\Applications\app\Services;

class FingerprintService
{
    /**
     * Compute the canonical SHA-256 fingerprint for a job opportunity.
     */
    public function generate(
        string $companyName,
        string $jobTitle,
        ?string $location = null,
        ?string $externalReqId = null,
        ?string $canonicalUrl = null
    ): string {
        $normCompany = $this->normalizeCompanyName($companyName);
        $normTitle = $this->normalizeJobTitle($jobTitle);
        $normLocation = $this->normalizeLocation($location);
        
        $keyIdentifier = !empty($externalReqId)
            ? strtolower(trim($externalReqId))
            : $this->normalizeUrl($canonicalUrl);

        $payload = "{$normCompany}::{$normTitle}::{$normLocation}::{$keyIdentifier}";

        return hash('sha256', $payload);
    }

    /**
     * Normalize company name by removing corporate designations and non-alphanumeric noise.
     */
    public function normalizeCompanyName(string $company): string
    {
        $company = mb_strtolower(trim($company), 'UTF-8');

        // Remove corporate designations: Inc, LLC, Ltd, Corp, Technologies, Solutions, Pvt Ltd, GmbH
        $suffixes = [
            '/\b(inc|incorporated)\b/i',
            '/\b(llc|l\.l\.c\.)\b/i',
            '/\b(ltd|limited)\b/i',
            '/\b(corp|corporation)\b/i',
            '/\b(pvt|private)\b/i',
            '/\b(gmbh|co|company|technologies|technology|tech|solutions|group)\b/i',
        ];

        foreach ($suffixes as $pattern) {
            $company = preg_replace($pattern, '', $company);
        }

        // Keep letters, digits, and single spaces
        $company = preg_replace('/[^a-z0-9]+/i', ' ', $company);
        return trim(preg_replace('/\s+/', ' ', $company));
    }

    /**
     * Normalize job title by standardizing common synonyms and seniority qualifiers.
     */
    public function normalizeJobTitle(string $title): string
    {
        $title = mb_strtolower(trim($title), 'UTF-8');

        $replacements = [
            '/\bsr\.?\b/i' => 'senior',
            '/\bjr\.?\b/i' => 'junior',
            '/\bmid-level\b/i' => 'mid',
            '/\bsw\b/i' => 'software',
            '/\beng\.?\b/i' => 'engineer',
            '/\bdev\.?\b/i' => 'developer',
            '/\bphp\s*\/\s*laravel\b/i' => 'laravel php',
            '/\blaravel\s*\/\s*php\b/i' => 'laravel php',
            '/\bwp\b/i' => 'wordpress',
            '/\bwoo\b/i' => 'woocommerce',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $title = preg_replace($pattern, $replacement, $title);
        }

        $title = preg_replace('/[^a-z0-9]+/i', ' ', $title);
        return trim(preg_replace('/\s+/', ' ', $title));
    }

    /**
     * Normalize location to standardized city/country tokens or 'remote'.
     */
    public function normalizeLocation(?string $location): string
    {
        if (empty($location)) {
            return 'remote';
        }

        $location = mb_strtolower(trim($location), 'UTF-8');

        if (preg_replace('/[^a-z]/', '', $location) === 'remote' || str_contains($location, 'worldwide') || str_contains($location, 'anywhere')) {
            return 'remote';
        }

        // Standardize common tech hubs
        if (str_contains($location, 'bangalore') || str_contains($location, 'bengaluru')) {
            return 'bengaluru_in';
        }

        if (str_contains($location, 'san francisco') || str_contains($location, 'sf')) {
            return 'san_francisco_us';
        }

        if (str_contains($location, 'london')) {
            return 'london_uk';
        }

        $location = preg_replace('/[^a-z0-9]+/i', '_', $location);
        return trim($location, '_');
    }

    /**
     * Strip tracking query parameters, hashes, and protocol prefixes.
     */
    public function normalizeUrl(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $parsed = parse_url(trim($url));
        $host = strtolower($parsed['host'] ?? '');
        $path = rtrim($parsed['path'] ?? '', '/');

        // Filter tracking query params if query string exists
        $cleanQuery = '';
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            $trackingKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'ref', 'source', 'fbclid', 'gclid'];
            foreach ($trackingKeys as $tKey) {
                unset($queryParams[$tKey]);
            }
            if (!empty($queryParams)) {
                ksort($queryParams);
                $cleanQuery = '?' . http_build_query($queryParams);
            }
        }

        return "{$host}{$path}{$cleanQuery}";
    }
}

