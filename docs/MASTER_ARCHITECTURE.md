# JobPilot AI — Master Architecture Specification

## 1. Executive Summary
JobPilot AI is architected as a **Secure Modular Monolith** written in PHP 8.3+ on Laravel 13. It leverages TiDB Cloud Starter (MySQL 8.0 protocol) for transactional persistence, Redis for queues and caching, local private storage for active documents, and Google Drive / Google Sheets for long-term historical records and human-readable archives.

## 2. Core Architectural Principles
1. **Free-First Resource Policy (`FREE_RESOURCES_ONLY=true`)**:
   - Strictly prohibits calls to paid models/APIs without verified free tiers and explicit authorization.
   - Dynamic model/quota discovery with graceful provider fallback.
2. **Permanent Identity & Deduplication**:
   - SHA-256 canonical vacancy fingerprints.
   - Registry maintains cross-platform application records permanently even after monthly archiving.
3. **Domain Modular Isolation**:
   - Encapsulated modules communicating via defined Contracts, DTOs, and Domain Events.
   - Thin controllers delegating orchestration to Actions and Services.
   - Strict database ownership: cross-module joins or direct mutation of another module's state is prohibited.
4. **Security & Human-in-the-Loop Safety**:
   - Encrypted provider credentials (`APP_KEY` AES-256-GCM).
   - SSRF-protected external URL fetching.
   - Isolated, shell-escape disabled compilation for LaTeX CVs.
   - Never auto-send without configured mode verification (Manual / Assisted / Automatic with daily limits).

## 3. High-Level System Architecture

```mermaid
flowchart TD
    User([User / Browser]) <--> UI[Bootstrap 5 + Vanilla JS Blade UI]
    UI <--> HTTP[Laravel 13 HTTP Kernel / Fortify Auth]

    subgraph Modular_Monolith [JobPilot AI Modular Core]
        Identity[Identity & Users]
        Dashboard[Dashboard & Monitoring]
        RoleProfiles[Role Profiles]
        Resume[Resume & CV Studio]
        Jobs[Job Discovery & Normalizer]
        Applications[Application Registry & Engine]
        Email[Email Automation Engine]
        AI[AI Gateway & Orchestrator]
        Archives[Google Drive & Sheets Archive]
        LinkedIn[LinkedIn Optimizer]
        Settings[Central Integration Manager]
    end

    HTTP --> Identity
    HTTP --> Dashboard
    HTTP --> RoleProfiles
    HTTP --> Resume
    HTTP --> Jobs
    HTTP --> Applications
    HTTP --> Email
    HTTP --> AI
    HTTP --> Archives
    HTTP --> LinkedIn
    HTTP --> Settings

    subgraph Storage_Tier [Data & Cache Tier]
        TiDB[(TiDB Cloud / MySQL)]
        Redis[(Redis Queue & Locks)]
        LocalFS[(Local Protected Storage)]
    end

    subgraph External_Services [External Cloud & Free APIs]
        GDrive[Google Drive & Sheets API]
        MailAPIs[Gmail API / Microsoft Graph]
        JobAPIs[Adzuna / Jooble / Himalayas / Remotive / Tavily]
        AIAPIs[Google AI Studio / Groq / OpenRouter / Ollama]
    end

    Applications <--> TiDB
    Jobs <--> TiDB
    Email <--> Redis
    Archives <--> GDrive
    Email <--> MailAPIs
    Jobs <--> JobAPIs
    AI <--> AIAPIs
```

## 4. Multi-Tenant / Multi-User Readiness
While initially deployed for personal single-user productivity, every data entity and queue payload includes `user_id` foreign keys and tenant-scoping policies. Transitioning to multi-user requires only enabling public/invite registration in `Settings` without schema rewrites.

