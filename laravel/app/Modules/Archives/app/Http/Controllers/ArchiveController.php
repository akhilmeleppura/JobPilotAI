<?php

namespace App\Modules\Archives\app\Http\Controllers;

use App\Modules\Archives\app\Models\ApplicationArchive;
use App\Modules\Archives\app\Services\ArchivePackagingService;
use App\Modules\Archives\app\Services\GoogleDriveArchiveService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArchiveController extends Controller
{
    public function __construct(
        protected ArchivePackagingService $packagingService,
        protected GoogleDriveArchiveService $driveService
    ) {}

    /**
     * Display long-term archive records and packaging status.
     */
    public function index(): View
    {
        $userId = Auth::id();
        $archives = ApplicationArchive::where('user_id', $userId)
            ->orderByDesc('archive_year')
            ->orderByDesc('archive_month')
            ->get();

        // Default to current year & month for new archive trigger form
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        return view('archives::index', compact('archives', 'currentYear', 'currentMonth'));
    }

    /**
     * Generate or re-generate monthly archive package.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $user = Auth::user();
        $archive = $this->packagingService->generateMonthlyArchive(
            $user,
            (int) $validated['year'],
            (int) $validated['month']
        );

        return redirect()->route('archives.index')->with(
            'success',
            "Monthly archive for {$archive->formatted_period} generated ({$archive->records_count} records, SHA-256 verified)!"
        );
    }

    /**
     * Download compressed JSONL.gz archive file.
     */
    public function downloadJsonl(int $id)
    {
        $archive = ApplicationArchive::where('user_id', Auth::id())->findOrFail($id);
        $fullPath = storage_path("app/{$archive->jsonl_filename}");

        if (!File::exists($fullPath)) {
            return back()->withErrors(['error' => 'Archive file not found on disk. Please re-generate.']);
        }

        return response()->streamDownload(function () use ($fullPath) {
            readfile($fullPath);
        }, basename($archive->jsonl_filename), [
            'Content-Type' => 'application/gzip',
        ]);
    }

    /**
     * Download human-readable CSV spreadsheet.
     */
    public function downloadCsv(int $id)
    {
        $archive = ApplicationArchive::where('user_id', Auth::id())->findOrFail($id);
        $fullPath = storage_path("app/{$archive->csv_filename}");

        if (!File::exists($fullPath)) {
            return back()->withErrors(['error' => 'Spreadsheet not found on disk. Please re-generate.']);
        }

        return response()->streamDownload(function () use ($fullPath) {
            readfile($fullPath);
        }, basename($archive->csv_filename), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Sync archive to Google Drive and Google Sheets.
     */
    public function syncDrive(int $id): RedirectResponse
    {
        $archive = ApplicationArchive::where('user_id', Auth::id())->findOrFail($id);
        $result = $this->driveService->syncArchive($archive);

        if ($result['success']) {
            return redirect()->route('archives.index')->with('success', $result['message']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * Verify on-disk SHA-256 integrity against database.
     */
    public function verifyChecksum(int $id): RedirectResponse
    {
        $archive = ApplicationArchive::where('user_id', Auth::id())->findOrFail($id);
        $jsonlPath = storage_path("app/{$archive->jsonl_filename}");

        if (!File::exists($jsonlPath)) {
            return back()->withErrors(['error' => 'Archive file not found on disk.']);
        }

        $calculated = hash_file('sha256', $jsonlPath);

        if ($calculated === $archive->jsonl_checksum_sha256) {
            $archive->update(['status' => 'Verified']);
            return redirect()->route('archives.index')->with(
                'success',
                "Integrity Verified! SHA-256 matches exactly: {$calculated}"
            );
        }

        return back()->withErrors(['error' => "Checksum mismatch! Expected {$archive->jsonl_checksum_sha256}, got {$calculated}."]);
    }
}
