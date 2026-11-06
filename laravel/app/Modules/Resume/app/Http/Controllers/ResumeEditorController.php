<?php

namespace App\Modules\Resume\app\Http\Controllers;

use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Models\ResumeVersion;
use App\Modules\Resume\app\Services\ATSAnalysisService;
use App\Modules\Resume\app\Services\LatexCompilerService;
use App\Modules\Resume\app\Services\ResumeTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResumeEditorController extends Controller
{
    public function __construct(
        protected ResumeTemplateService $templateService,
        protected LatexCompilerService $compilerService,
        protected ATSAnalysisService $atsService
    ) {}

    /**
     * Display the LaTeX Editor & live preview workbench.
     */
    public function index(Request $request, int $id): View|RedirectResponse
    {
        $user = Auth::user();
        $resume = Resume::with(['versions' => fn($q) => $q->orderByDesc('version_number'), 'currentVersion', 'roleProfile'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $selectedVersionNumber = $request->query('version');
        $activeVersion = $selectedVersionNumber
            ? $resume->versions->firstWhere('version_number', (int) $selectedVersionNumber)
            : $resume->currentVersion;

        if (!$activeVersion) {
            $activeVersion = $resume->versions->first();
        }

        $templates = $this->templateService->getAvailableTemplates();
        $atsAnalysis = $this->atsService->analyze($activeVersion?->latex_source ?? '');

        return view('resume::editor.index', compact('resume', 'activeVersion', 'templates', 'atsAnalysis'));
    }

    /**
     * Compile LaTeX source and return JSON status with preview link and logs.
     */
    public function compile(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $resume = Resume::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'latex_source' => ['required', 'string'],
            'version_id' => ['nullable', 'integer'],
        ]);

        $latexSource = $request->input('latex_source');
        $versionId = $request->input('version_id');
        $version = $versionId ? ResumeVersion::where('resume_id', $resume->id)->find($versionId) : $resume->currentVersion;
        $versionNumber = $version?->version_number ?? 1;

        try {
            $compileResult = $this->compilerService->compile($latexSource, $resume->id, $versionNumber);
            $atsAnalysis = $this->atsService->analyze($latexSource);

            // Auto-save changes to the active version
            if ($version) {
                $version->update([
                    'latex_source' => $latexSource,
                    'pdf_path' => $compileResult['pdf_path'],
                    'ats_score' => $atsAnalysis['score'],
                ]);
            }

            return response()->json([
                'success' => $compileResult['success'],
                'pdf_url' => route('resume.editor.preview-pdf', ['id' => $resume->id, 'v' => $versionNumber, 't' => time()]),
                'logs' => $compileResult['logs'],
                'engine' => $compileResult['engine'],
                'duration_ms' => $compileResult['duration_ms'],
                'ats' => $atsAnalysis,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'pdf_url' => null,
                'logs' => "Error: " . $e->getMessage(),
                'engine' => 'Error',
                'duration_ms' => 0,
                'ats' => $this->atsService->analyze($latexSource),
            ], 422);
        }
    }

    /**
     * Stream PDF for inline preview in iframe.
     */
    public function previewPdf(Request $request, int $id): StreamedResponse
    {
        $user = Auth::user();
        $resume = Resume::where('user_id', $user->id)->findOrFail($id);
        $versionNumber = (int) $request->query('v', 1);

        $pdfPath = storage_path("app/resumes/resume_{$resume->id}_v{$versionNumber}.pdf");

        if (!File::exists($pdfPath)) {
            $version = ResumeVersion::where('resume_id', $resume->id)->where('version_number', $versionNumber)->first();
            $source = $version?->latex_source ?? $this->templateService->getTemplateSource('ats_classic', $user);
            $result = $this->compilerService->compile($source, $resume->id, $versionNumber);
            $pdfPath = $result['pdf_path'] ?? $pdfPath;
        }

        return response()->stream(
            function () use ($pdfPath) {
                if (File::exists($pdfPath)) {
                    readfile($pdfPath);
                }
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="resume.pdf"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Download compiled PDF using Windows-safe stream download.
     */
    public function downloadPdf(int $id, ?int $versionNumber = null): StreamedResponse
    {
        $user = Auth::user();
        $resume = Resume::where('user_id', $user->id)->findOrFail($id);

        $vNumber = $versionNumber ?? ($resume->currentVersion?->version_number ?? 1);
        $pdfPath = storage_path("app/resumes/resume_{$resume->id}_v{$vNumber}.pdf");

        if (!File::exists($pdfPath)) {
            $version = ResumeVersion::where('resume_id', $resume->id)->where('version_number', $vNumber)->first();
            $source = $version?->latex_source ?? $this->templateService->getTemplateSource('ats_classic', $user);
            $result = $this->compilerService->compile($source, $resume->id, $vNumber);
            $pdfPath = $result['pdf_path'] ?? $pdfPath;
        }

        $filename = Str::slug($resume->title) . "_v{$vNumber}.pdf";

        return response()->streamDownload(
            function () use ($pdfPath) {
                if (File::exists($pdfPath)) {
                    readfile($pdfPath);
                }
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Download raw LaTeX source file (.tex).
     */
    public function downloadTex(int $id, ?int $versionNumber = null): StreamedResponse
    {
        $user = Auth::user();
        $resume = Resume::where('user_id', $user->id)->findOrFail($id);

        $vNumber = $versionNumber ?? ($resume->currentVersion?->version_number ?? 1);
        $version = ResumeVersion::where('resume_id', $resume->id)->where('version_number', $vNumber)->first();
        $source = $version?->latex_source ?? '';
        $filename = Str::slug($resume->title) . "_v{$vNumber}.tex";

        return response()->streamDownload(
            function () use ($source) {
                echo $source;
            },
            $filename,
            ['Content-Type' => 'text/x-tex; charset=utf-8']
        );
    }

    /**
     * Create a new immutable version checkpoint.
     */
    public function saveVersion(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $resume = Resume::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'latex_source' => ['required', 'string'],
            'optimization_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $highestVersion = ResumeVersion::where('resume_id', $resume->id)->max('version_number') ?? 0;
        $newVersionNumber = $highestVersion + 1;

        $ats = $this->atsService->analyze($request->input('latex_source'));

        // Compile to ensure PDF exists for this version
        $compileResult = $this->compilerService->compile($request->input('latex_source'), $resume->id, $newVersionNumber);

        $newVersion = ResumeVersion::create([
            'resume_id' => $resume->id,
            'version_number' => $newVersionNumber,
            'template_name' => $resume->currentVersion?->template_name ?? 'ats_classic',
            'source_type' => 'LaTeX',
            'latex_source' => $request->input('latex_source'),
            'pdf_path' => $compileResult['pdf_path'],
            'ats_score' => $ats['score'],
            'optimization_notes' => $request->input('optimization_notes') ?: "Version {$newVersionNumber} checkpoint.",
        ]);

        $resume->update(['current_version_id' => $newVersion->id]);

        return redirect()->route('resume.editor.index', ['id' => $resume->id, 'version' => $newVersionNumber])
            ->with('success', "New version #{$newVersionNumber} saved successfully (ATS Score: {$ats['score']}%).");
    }

    /**
     * Apply a new template layout to the active editor.
     */
    public function applyTemplate(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $resume = Resume::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'template_key' => ['required', 'string'],
        ]);

        $templateKey = $request->input('template_key');
        $newSource = $this->templateService->getTemplateSource($templateKey, $user);
        $ats = $this->atsService->analyze($newSource);

        $highestVersion = ResumeVersion::where('resume_id', $resume->id)->max('version_number') ?? 0;
        $newVersionNumber = $highestVersion + 1;

        $compileResult = $this->compilerService->compile($newSource, $resume->id, $newVersionNumber);

        $newVersion = ResumeVersion::create([
            'resume_id' => $resume->id,
            'version_number' => $newVersionNumber,
            'template_name' => $templateKey,
            'source_type' => 'LaTeX',
            'latex_source' => $newSource,
            'pdf_path' => $compileResult['pdf_path'],
            'ats_score' => $ats['score'],
            'optimization_notes' => "Applied template '{$templateKey}'.",
        ]);

        $resume->update(['current_version_id' => $newVersion->id]);

        return redirect()->route('resume.editor.index', ['id' => $resume->id, 'version' => $newVersionNumber])
            ->with('success', "Template '{$templateKey}' applied as Version #{$newVersionNumber}.");
    }

    /**
     * Analyze current LaTeX content for ATS feedback.
     */
    public function analyzeAts(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'latex_source' => ['required', 'string'],
        ]);

        $analysis = $this->atsService->analyze($request->input('latex_source'));
        return response()->json($analysis);
    }
}

