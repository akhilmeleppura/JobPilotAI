<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use App\Modules\ImportExport\app\Models\ImportBatch;
use App\Modules\ImportExport\app\Services\DocumentParserService;
use App\Modules\ImportExport\app\Services\ImportNormalizationService;
use App\Modules\ImportExport\app\Services\SchemaMappingService;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SpreadsheetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(): User
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        app(ResumeService::class)->getOrSeedResumesForUser($user);
        return $user;
    }

    public function test_guests_are_redirected_from_import_studio(): void
    {
        $response = $this->get('/import');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_import_studio(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get('/import');
        $response->assertStatus(200);
        $response->assertSee('Spreadsheet & Document Import Studio', false);
        $response->assertSee('TOTAL IMPORTED APPLICATIONS');
        $response->assertSee('CSV / XLSX File Upload');
    }

    public function test_csv_document_parser_handles_comma_semicolon_and_bom(): void
    {
        $parser = app(DocumentParserService::class);

        // Comma delimited with BOM
        $csvContent = "\xEF\xBB\xBFCompany,Position,Status,Date\nAutomattic,Senior PHP Dev,Applied,2026-10-15\nBasecamp,Backend Eng,Interview,2026-10-18";
        $parsed = $parser->parseCsv($csvContent);

        $this->assertEquals(['Company', 'Position', 'Status', 'Date'], $parsed['headers']);
        $this->assertCount(2, $parsed['rows']);
        $this->assertEquals('Automattic', $parsed['rows'][0]['Company']);
        $this->assertEquals('Senior PHP Dev', $parsed['rows'][0]['Position']);

        // Semicolon delimited
        $semiCsv = "Employer;Role;Stage;Applied Date\nStripe;Lead Architect;Offer;2026-10-20\nGitLab;Systems Dev;Applied;2026-10-22";
        $parsedSemi = $parser->parseCsv($semiCsv);

        $this->assertEquals(['Employer', 'Role', 'Stage', 'Applied Date'], $parsedSemi['headers']);
        $this->assertCount(2, $parsedSemi['rows']);
        $this->assertEquals('Stripe', $parsedSemi['rows'][0]['Employer']);
    }

    public function test_schema_mapping_service_infers_canonical_columns(): void
    {
        $mapper = app(SchemaMappingService::class);

        $headers = ['Employer', 'Role', 'Stage', 'Applied On', 'Job Link', 'City'];
        $inference = $mapper->inferMapping($headers);

        $this->assertEquals(95, $inference['confidence']);
        $this->assertEquals('company_name', $inference['mapping']['Employer']);
        $this->assertEquals('job_title', $inference['mapping']['Role']);
        $this->assertEquals('status', $inference['mapping']['Stage']);
        $this->assertEquals('applied_date', $inference['mapping']['Applied On']);
        $this->assertEquals('apply_url', $inference['mapping']['Job Link']);
        $this->assertEquals('location', $inference['mapping']['City']);
        $this->assertEmpty($inference['missing_required']);
    }

    public function test_user_can_import_csv_file_and_auto_normalizes_records(): void
    {
        $user = $this->setupUser();

        $csvContent = "Company Name,Job Title,Application Status,Date Applied,Job URL\n"
            . "Automattic Inc.,Senior WordPress Engineer,Applied,2026-10-15,https://automattic.com/jobs/wp\n"
            . "Stripe LLC,Senior Backend Payments Architect,Interview,2026-10-20,https://stripe.com/jobs/pay\n"
            . "Buffer,Full Stack Engineer,Draft,2026-10-25,https://buffer.com/jobs/fs";

        $file = UploadedFile::fake()->createWithContent('applications_history.csv', $csvContent);

        $response = $this->actingAs($user)->post('/import/upload', [
            'file' => $file,
        ]);

        $response->assertRedirect('/applications');
        $response->assertSessionHas('success');

        $this->assertEquals(3, Application::where('user_id', $user->id)->count());
        $this->assertEquals(3, ApplicationRegistry::where('user_id', $user->id)->count());

        $batch = ImportBatch::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(3, $batch->total_rows);
        $this->assertEquals(3, $batch->imported_count);
        $this->assertEquals(0, $batch->duplicates_count);
        $this->assertEquals('Completed', $batch->status);
    }

    public function test_duplicate_applications_are_detected_and_skipped_during_import(): void
    {
        $user = $this->setupUser();

        $csvContent = "Company,Position,Status\n"
            . "Automattic,Senior WordPress Engineer,Applied\n"
            . "Stripe,Senior Backend Payments Architect,Interview";

        $file1 = UploadedFile::fake()->createWithContent('batch1.csv', $csvContent);
        $this->actingAs($user)->post('/import/upload', ['file' => $file1]);

        $this->assertEquals(2, Application::where('user_id', $user->id)->count());

        // Re-upload identical file
        $file2 = UploadedFile::fake()->createWithContent('batch2.csv', $csvContent);
        $this->actingAs($user)->post('/import/upload', ['file' => $file2]);

        // Count should still be 2, duplicate rows skipped
        $this->assertEquals(2, Application::where('user_id', $user->id)->count());

        $latestBatch = ImportBatch::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertEquals(0, $latestBatch->imported_count);
        $this->assertEquals(2, $latestBatch->duplicates_count);
    }

    public function test_user_can_import_raw_text_snippet(): void
    {
        $user = $this->setupUser();

        $snippet = "Company: Figma Inc.\nPosition: Staff Frontend Engineer\nStatus: Interview\nDate: 2026-10-28\nURL: https://figma.com/jobs/staff-eng";

        $response = $this->actingAs($user)->post('/import/upload', [
            'raw_text' => $snippet,
        ]);

        $response->assertRedirect('/applications');
        $response->assertSessionHas('success');

        $app = Application::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('Interview', $app->application_status);
        $this->assertStringContainsString('figma', strtolower($app->registry->company_name_normalized));
    }

    public function test_user_can_download_sample_csv_template(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get('/import/template');
        $response->assertStatus(200);
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}

