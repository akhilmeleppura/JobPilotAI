# JobPilot AI — Module Structure

## Architecture: Modular Monolith
The application adheres to high-cohesion, low-coupling domain boundaries.

```
laravel/app/Modules/
├── Identity/              # Authentication, Authorization, Users, Profiles, Permissions
├── Dashboard/             # Consolidated metrics, activity feeds, provider quotas
├── Candidate/             # Core candidate master records, skills, career history
├── RoleProfiles/          # Target role profiles (Backend, Full Stack, WordPress, etc.)
├── Resume/                # CV Studio, LaTeX compilation, versioning, ATS optimization
├── Jobs/                  # Discovery connectors (Adzuna, Jooble, etc.), search, normalization
├── Applications/          # Permanent application registry, SHA-256 deduplication
├── Email/                 # Provider adapters (Gmail API, MS Graph), sending engine, quota monitor
├── AI/                    # Free-first AI gateway (Gemini, Groq, OpenRouter, Ollama)
├── Integrations/          # Central settings, encrypted credentials vault, health checks
├── ImportExport/          # XLSX/CSV/PDF document parser, AI field mapper, exports
├── Archives/              # Google Drive upload, Monthly Sheets sync, JSONL.gz packaging
├── LinkedIn/              # Profile optimizer, CV comparison, keyword suggestions
├── ContentStudio/         # Technical post generator, image prompt builder, calendar
├── Automation/            # Task scheduling, auto-matching, batch email dispatch
├── Monitoring/            # TiDB storage, Redis health, API rate-limit monitoring
└── Settings/              # Application preferences, FREE_RESOURCES_ONLY enforcement
```

## Standard Internal Module Layout
```
Modules/ModuleName/
├── app/
│   ├── Contracts/        # Public module interfaces
│   ├── DTOs/             # Immutable data transfer objects
│   ├── Enums/            # Domain enums
│   ├── Events/           # Domain events emitted for cross-module consumption
│   ├── Exceptions/       # Domain-specific exceptions
│   ├── Http/
│   │   ├── Controllers/  # Lean controllers
│   │   ├── Middleware/   # Module-scoped middleware
│   │   ├── Requests/     # Strict Form Request validation
│   │   └── Resources/    # API/JSON transformation resources
│   ├── Jobs/             # Background queue jobs
│   ├── Listeners/        # Event listeners reacting to domain events
│   ├── Models/           # Eloquent entities
│   ├── Policies/         # Ownership & authorization policies
│   ├── Repositories/     # Data access abstraction
│   ├── Services/         # Business domain logic
│   └── Providers/        # ModuleServiceProvider registering routes, views, bindings
├── database/
│   ├── migrations/       # Module-specific database migrations
│   ├── factories/        # Test factories
│   └── seeders/          # Default/sample seeders
├── resources/
│   └── views/            # Blade templates
├── routes/
│   ├── web.php           # Web routes
│   └── api.php           # API routes
└── tests/
    ├── Feature/
    └── Unit/
```

## Communication Guidelines
1. **Synchronous Cross-Module Calls**: Call strictly through `Contracts` injected via the Service Container. Never import another module's internal concrete classes or directly execute database mutations on its tables.
2. **Asynchronous Cross-Module Calls**: Dispatch `Domain Events` handled by other modules' queued listeners.
3. **Shared Infrastructure**: Placed in `app/Shared/` for reusable interfaces, pagination helpers, and core DTO primitives.

