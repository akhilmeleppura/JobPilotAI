# JobPilot AI — Database Architecture & TiDB Design

## Overview
JobPilot AI uses **TiDB Cloud Starter** (accessed via Laravel's MySQL PDO driver). The schema is engineered to remain strictly ANSI/MySQL 8.0 compatible, avoiding unsupported TiDB constructs (e.g. database triggers, stored procedures, unsupported spatial/fulltext quirks).

## Core Data Models & Schemas

### 1. Identity & Role Profiles
- `users`: Standard Laravel user records with `is_admin`, `public_registration` flags.
- `role_profiles`:
  - `id`, `user_id`, `name`, `target_titles` (JSON), `technical_skills` (JSON), `secondary_skills` (JSON), `experience_years`, `summary`, `location_preferences` (JSON), `work_modes` (JSON: Remote, Hybrid, Onsite), `salary_min`, `salary_max`, `currency`, `search_keywords` (JSON), `default_resume_id`, `email_signature`, `is_active`, `timestamps`.

### 2. Resumes & CV Studio
- `resumes`: `id`, `user_id`, `role_profile_id` (nullable), `title`, `current_version_id`, `created_at`, `updated_at`.
- `resume_versions`:
  - `id`, `resume_id`, `version_number`, `template_name`, `source_type` (LaTeX, JSON, Markdown), `latex_source`, `parsed_content` (JSON: summary, experience, skills, projects, education), `pdf_path`, `ats_score`, `optimization_notes`, `created_at`.

### 3. Job Vacancies & Discovery
- `companies`: `id`, `name`, `domain`, `website`, `career_page_url`, `location`, `created_at`.
- `job_vacancies`:
  - `id`, `company_id`, `company_name`, `job_title`, `location`, `work_mode`, `employment_type`, `experience_min`, `experience_max`, `salary_min`, `salary_max`, `currency`, `skills` (JSON), `description` (TEXT/LONGTEXT), `posted_date`, `application_deadline`, `apply_method`, `apply_url`, `source_url`, `source_provider`, `external_job_id`, `hr_name`, `hr_email`, `hr_email_source_url`, `vacancy_fingerprint` (CHAR 64 SHA-256), `status`, `created_at`.

### 4. Permanent Application Registry (Mission-Critical)
- `application_registry`:
  - `id`, `user_id`, `canonical_fingerprint` (CHAR 64, UNIQUE index), `company_name_normalized`, `job_title_normalized`, `external_requisition_id`, `source_url_hash`, `first_discovered_at`, `status` (Discovered, Matched, Staged, Applied, Archived).
- `application_identity_keys`:
  - `id`, `registry_id`, `key_type` (SHA256_FINGERPRINT, REQ_ID, APPLY_URL, CANONICAL_URL), `key_value` (VARCHAR 255), `created_at`.
- `applications`:
  - `id`, `user_id`, `registry_id`, `job_vacancy_id`, `role_profile_id`, `resume_id`, `resume_version_id`, `applied_via` (Email, Portal, LinkedIn, Direct), `discovered_from`, `email_template_id`, `email_account_id`, `application_status` (Draft, Ready, Sent, Failed, Replied, Interview, Offer, Rejected), `email_delivery_status` (Pending, Sent, Failed, Bounced), `sent_at`, `created_at`.
- `application_events`:
  - `id`, `application_id`, `event_type` (STATUS_CHANGE, EMAIL_SENT, REPLY_DETECTED, NOTE_ADDED), `event_payload` (JSON), `created_at`.
- `application_archives`:
  - `id`, `user_id`, `archive_year`, `archive_month`, `records_count`, `gdrive_file_id`, `gdrive_sheet_id`, `jsonl_checksum_sha256`, `archived_at`.

### 5. Email Automation & Providers
- `email_accounts`:
  - `id`, `user_id`, `provider_type` (Gmail, Graph, Zoho), `email_address`, `encrypted_credentials`, `daily_cap` (default 200), `sent_today`, `last_reset_date`, `is_active`, `pause_reason`.
- `email_logs`:
  - `id`, `application_id` (nullable), `email_account_id`, `to_recipients` (JSON), `cc_recipients` (JSON), `bcc_recipients` (JSON), `subject`, `status`, `provider_message_id`, `error_message`, `sent_at`.
- `email_templates`:
  - `id`, `user_id`, `category`, `name`, `subject_template`, `body_template`, `default_cc` (JSON), `created_at`.

### 6. AI Gateway & Settings
- `ai_provider_configs`:
  - `id`, `provider_name` (Gemini, Groq, OpenRouter, Ollama), `encrypted_api_key`, `is_enabled`, `is_free_tier`, `requests_today`, `input_tokens_today`, `output_tokens_today`.
- `ai_request_logs`:
  - `id`, `user_id`, `provider`, `model`, `prompt_tokens`, `completion_tokens`, `cost_estimate`, `status`, `created_at`.

