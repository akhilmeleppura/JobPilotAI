<?php

namespace App\Modules\ImportExport\app\Services;

class SchemaMappingService
{
    protected const CANONICAL_FIELDS = [
        'company_name' => [
            'label' => 'Company Name',
            'required' => true,
            'synonyms' => ['company', 'company_name', 'organization', 'employer', 'firm', 'business', 'client'],
        ],
        'job_title' => [
            'label' => 'Job Title',
            'required' => true,
            'synonyms' => ['job_title', 'title', 'position', 'role', 'vacancy', 'designation', 'job'],
        ],
        'status' => [
            'label' => 'Application Status',
            'required' => false,
            'synonyms' => ['status', 'stage', 'state', 'application_status', 'result', 'outcome', 'progress'],
        ],
        'applied_date' => [
            'label' => 'Date Applied',
            'required' => false,
            'synonyms' => ['applied_date', 'date_applied', 'date', 'submission_date', 'sent_at', 'applied_on', 'timestamp', 'created_at'],
        ],
        'apply_url' => [
            'label' => 'Job URL / Link',
            'required' => false,
            'synonyms' => ['apply_url', 'job_url', 'url', 'link', 'source_url', 'posting_url', 'website'],
        ],
        'location' => [
            'label' => 'Location / Work Mode',
            'required' => false,
            'synonyms' => ['location', 'city', 'country', 'place', 'workplace', 'region', 'work_mode'],
        ],
        'notes' => [
            'label' => 'Notes / Details',
            'required' => false,
            'synonyms' => ['notes', 'comments', 'feedback', 'details', 'remarks', 'description'],
        ],
        'salary' => [
            'label' => 'Compensation',
            'required' => false,
            'synonyms' => ['salary', 'compensation', 'pay', 'rate', 'wage'],
        ],
    ];

    /**
     * Get list of canonical schema fields.
     */
    public function getCanonicalFields(): array
    {
        return self::CANONICAL_FIELDS;
    }

    /**
     * Infer the best column mapping for an array of incoming headers.
     *
     * @param array<string> $headers
     * @return array{
     *     mapping: array<string, string>,
     *     confidence: int,
     *     unmapped_headers: array<string>,
     *     missing_required: array<string>
     * }
     */
    public function inferMapping(array $headers): array
    {
        $mapping = [];
        $unmapped = [];
        $matchedFields = [];

        foreach ($headers as $header) {
            $normHeader = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $header), '_'));
            $matchedKey = null;

            foreach (self::CANONICAL_FIELDS as $fieldKey => $info) {
                if (in_array($fieldKey, $matchedFields)) {
                    continue; // Already mapped
                }

                if ($normHeader === $fieldKey) {
                    $matchedKey = $fieldKey;
                    break;
                }

                foreach ($info['synonyms'] as $syn) {
                    if ($normHeader === $syn || str_contains($normHeader, $syn)) {
                        $matchedKey = $fieldKey;
                        break 2;
                    }
                }
            }

            if ($matchedKey) {
                $mapping[$header] = $matchedKey;
                $matchedFields[] = $matchedKey;
            } else {
                $unmapped[] = $header;
            }
        }

        // Check required fields
        $missingRequired = [];
        foreach (self::CANONICAL_FIELDS as $fieldKey => $info) {
            if ($info['required'] && !in_array($fieldKey, $matchedFields)) {
                $missingRequired[] = $info['label'];
            }
        }

        $confidence = empty($missingRequired) ? 95 : 50;

        return [
            'mapping' => $mapping,
            'confidence' => $confidence,
            'unmapped_headers' => $unmapped,
            'missing_required' => $missingRequired,
        ];
    }
}

