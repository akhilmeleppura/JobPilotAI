<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with designated QA test accounts.
     */
    public function run(): void
    {
        $password = Hash::make('Password123!');

        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@jobpilot.test'],
            [
                'name' => 'System Administrator',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        // 2. Standard Candidate User
        $candidate = User::firstOrCreate(
            ['email' => 'candidate@jobpilot.test'],
            [
                'name' => 'Alex LeadDeveloper',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        // 3. Unauthorized / Separate Tenant User (for IDOR and isolation testing)
        $unauthorized = User::firstOrCreate(
            ['email' => 'unauthorized@jobpilot.test'],
            [
                'name' => 'Separate Tenant User',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        // Seed Role Profiles & Resume for the candidate
        $roleProfileService = app(RoleProfileService::class);
        $roleProfileService->seedDefaultProfilesForUser($candidate);

        $resumeService = app(ResumeService::class);
        $resumeService->getOrSeedResumesForUser($candidate);

        $vaultService = app(CredentialVaultService::class);
        $vaultService->getOrSeedIntegrationsForUser($candidate);
    }
}
