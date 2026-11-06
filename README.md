# JobPilot AI

JobPilot AI is an AI-powered personal job application management platform designed to automate and optimize the entire career search lifecycle.

## Overview
- **Natural-Language AI Job Discovery**: Integrates multiple job APIs and search engines.
- **Permanent Application History & Fingerprinting**: SHA-256 deduplication index preventing duplicate job applications across platforms.
- **Multiple Role & Career Profile Management**: Match vacancies against distinct profiles (Laravel/PHP, WordPress, Full Stack, Freelance).
- **CV Studio & Overleaf-Compatible LaTeX Editor**: Tailor, version, and compile ATS-optimized resumes.
- **AI Email Automation Engine**: Personalized email outreach with quota tracking, CC/BCC management, and human-in-the-loop review.
- **Google Drive & Monthly Sheets Long-Term Archive**: Durable multi-tier archiving with JSONL.gz backups and monthly spreadsheet syncing.
- **LinkedIn Profile Optimizer & Content Studio**: Generate recruiter-optimized headlines, summaries, and career posts.
- **Secure Modular Monolith**: Built with Laravel 13, Bootstrap 5, Vanilla JS, and TiDB Cloud compatibility.
- **Free-First Resource Policy**: Enforces strict `FREE_RESOURCES_ONLY=true` checks across AI, email, and job discovery APIs.

---

## Directory Structure
```
JobPilotAI/
├── docs/                 # System architecture, specs, and roadmaps
├── laravel/              # Laravel 13 Modular Monolith application
│   ├── app/
│   │   ├── Modules/      # Domain modules (Identity, Jobs, Applications, etc.)
│   │   └── Shared/       # Cross-cutting contracts and utilities
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   └── routes/
├── backups/              # Local backup staging directory
└── README.md
```

## Documentation
- [Master Architecture](docs/MASTER_ARCHITECTURE.md)
- [Roadmap](docs/ROADMAP.md)
- [Module Structure](docs/MODULE_STRUCTURE.md)
- [Database Design](docs/DATABASE_DESIGN.md)
- [Security Guidelines](docs/SECURITY.md)
- [Email Providers](docs/EMAIL_PROVIDERS.md)
- [Job Sources](docs/JOB_SOURCES.md)
- [Application History](docs/APPLICATION_HISTORY.md)
- [Google Drive & Sheets Archive](docs/GOOGLE_DRIVE_ARCHIVE.md)
- [AI Providers](docs/AI_PROVIDERS.md)
- [CV Studio](docs/CV_STUDIO.md)
- [LinkedIn Optimizer](docs/LINKEDIN_OPTIMIZER.md)
- [Hosting & Deployment](docs/HOSTING.md)
- [Development Log](docs/DEVELOPMENT_LOG.md)

