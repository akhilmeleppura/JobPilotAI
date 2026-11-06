<?php

namespace App\Modules\ImportExport\app\Http\Controllers;

use App\Modules\ImportExport\app\Models\ImportBatch;
use App\Modules\ImportExport\app\Services\DocumentParserService;
use App\Modules\ImportExport\app\Services\ImportNormalizationService;
use App\Modules\ImportExport\app\Services\SchemaMappingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    public function __construct(
        protected DocumentParserService $parserService,
        protected SchemaMappingService $mappingService,
        protected ImportNormalizationService $normalizationService
    ) {}

    /**
     * Display Document & Spreadsheet Import Studio.
     */
    public function index(): View
    {
        $user = Auth::user();
        $batches = ImportBatch::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $metrics = [
            'total_imported' => ImportBatch::where('user_id', $user->id)->sum('imported_count'),
            'total_batches' => ImportBatch::where('user_id', $user->id)->count(),
            'total_duplicates' => ImportBatch::where('user_id', $user->id)->sum('duplicates_count'),
        ];

        $canonicalFields = $this->mappingService->getCanonicalFields();

        return view('importexport::import.index', compact('batches', 'metrics', 'canonicalFields'));
    }

    /**
     * Process file upload, Google Sheet link, or text snippet.
     */
    public function upload(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'file' => ['nullable', 'file', 'max:10240'], // 10MB
            'google_sheets_url' => ['nullable', 'url'],
            'raw_text' => ['nullable', 'string'],
        ]);

        $headers = [];
        $rows = [];
        $filename = 'import.csv';
        $fileType = 'CSV';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = $file->getClientOriginalName();

            if ($extension === 'xlsx') {
                $fileType = 'XLSX';
                $parsed = $this->parserService->parseXlsx($file->getRealPath());
            } else {
                $fileType = 'CSV';
                $parsed = $this->parserService->parseCsv($file->getRealPath());
            }

            $headers = $parsed['headers'];
            $rows = $parsed['rows'];
        } elseif ($url = $request->input('google_sheets_url')) {
            $fileType = 'GoogleSheets';
            $filename = 'Google_Sheet_' . date('Y_m_d_His') . '.csv';
            $parsed = $this->parserService->parseGoogleSheetUrl($url);
            $headers = $parsed['headers'];
            $rows = $parsed['rows'];
        } elseif ($text = $request->input('raw_text')) {
            $fileType = 'Text';
            $filename = 'Text_Snippet_' . date('Y_m_d_His') . '.txt';
            $parsed = $this->parserService->parseTextSnippet($text);
            $headers = $parsed['headers'];
            $rows = $parsed['rows'];
        } else {
            return redirect()->route('import.index')->with('error', 'Please provide a CSV/XLSX file, Google Sheets URL, or application text snippet.');
        }

        if (empty($rows)) {
            return redirect()->route('import.index')->with('error', 'No valid rows found in the provided document.');
        }

        $inference = $this->mappingService->inferMapping($headers);

        // If confidence is high (both Company and Job Title mapped) and auto_import requested, proceed directly
        if ($inference['confidence'] >= 90 && empty($inference['missing_required']) && !$request->has('review_mapping')) {
            $result = $this->normalizationService->importRows(
                user: $user,
                rows: $rows,
                mapping: $inference['mapping'],
                filename: $filename,
                fileType: $fileType
            );

            return redirect()->route('applications.index')->with(
                'success',
                "Successfully imported {$result['imported']} applications from '{$filename}'. ({$result['duplicates']} duplicates skipped)."
            );
        }

        // Cache rows in session for mapping confirmation
        Session::put('pending_import', [
            'filename' => $filename,
            'file_type' => $fileType,
            'headers' => $headers,
            'rows' => $rows,
            'inferred_mapping' => $inference['mapping'],
        ]);

        $canonicalFields = $this->mappingService->getCanonicalFields();

        return view('importexport::import.mapping', [
            'filename' => $filename,
            'fileType' => $fileType,
            'headers' => $headers,
            'sampleRows' => array_slice($rows, 0, 3),
            'inferredMapping' => $inference['mapping'],
            'missingRequired' => $inference['missing_required'],
            'canonicalFields' => $canonicalFields,
        ]);
    }

    /**
     * Confirm column mapping and execute import.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $pending = Session::get('pending_import');

        if (!$pending) {
            return redirect()->route('import.index')->with('error', 'Session expired. Please re-upload your document.');
        }

        $mapping = $request->input('mapping', []);
        $cleanMapping = array_filter($mapping, fn($target) => !empty($target) && $target !== 'ignore');

        $result = $this->normalizationService->importRows(
            user: $user,
            rows: $pending['rows'],
            mapping: $cleanMapping,
            filename: $pending['filename'],
            fileType: $pending['file_type']
        );

        Session::forget('pending_import');

        return redirect()->route('applications.index')->with(
            'success',
            "Successfully imported {$result['imported']} records from '{$pending['filename']}'. ({$result['duplicates']} duplicates preserved in registry)."
        );
    }

    /**
     * Download standard sample CSV template.
     */
    public function template(): StreamedResponse
    {
        $headers = ['Company Name', 'Job Title', 'Application Status', 'Date Applied', 'Job URL', 'Location', 'Notes'];
        $sampleData = [
            ['Automattic', 'Senior WordPress Engineer', 'Applied', '2026-10-15', 'https://automattic.com/jobs/101', 'Remote', 'Reached out via direct portal'],
            ['Stripe', 'Senior Backend Engineer', 'Interview', '2026-10-20', 'https://stripe.com/jobs/202', 'Remote', 'Completed screening round'],
            ['GitLab', 'Lead PHP Developer', 'Draft', '2026-10-25', 'https://about.gitlab.com/jobs/303', 'Remote', 'Staged for initial follow-up outreach'],
        ];

        return response()->streamDownload(
            function () use ($headers, $sampleData) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $headers);
                foreach ($sampleData as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            },
            'jobpilot_application_history_template.csv',
            ['Content-Type' => 'text/csv; charset=utf-8']
        );
    }
}

