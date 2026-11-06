# JobPilot AI — Google Drive & Monthly Sheets Long-Term Archive

## 1. Archival Architecture
To guarantee permanent records without overloading the TiDB Cloud Starter transactional database, JobPilot AI uses a tiered archive strategy:
1. **Tier 1 (Hot / Operational)**: TiDB Cloud holds active vacancies, pending applications, email accounts, and the permanent `application_registry` deduplication index.
2. **Tier 2 (Cold / Long-Term)**: Google Drive acts as the durable vault for historical application payloads, generated PDFs, and monthly audit summaries.
3. **Tier 3 (Human-Readable Overview)**: Google Sheets automatically receives monthly synced summaries.

## 2. Directory Hierarchy in Google Drive
```
JobPilot AI/
└── Application History/
    └── {YEAR}/
        └── {MONTH}/
            ├── Applications_{YEAR}_{MONTH}.csv
            ├── Application_Events_{YEAR}_{MONTH}.jsonl.gz
            ├── Email_Outreach_Summary_{YEAR}_{MONTH}.xlsx
            ├── Platform_Distribution_{YEAR}_{MONTH}.pdf
            └── Monthly_Archive_Checksums.sha256
```

## 3. Automation Cycles
- **Immediate Post-Application**:
  - Hash and persist permanent identity keys in `application_registry`.
  - Record application state in `applications`.
  - Enqueue asynchronous sync job for long-term audit trail.
- **Daily Cycle**:
  - Extract completed applications from previous 24 hours.
  - Compile streaming JSONL.gz compressed package.
  - Compute SHA-256 checksum, upload to Drive, verify byte match.
- **Monthly Rollup Cycle**:
  - Lock previous month's partition.
  - Generate consolidated Google Sheet via Google Sheets API.
  - Retain permanent identity fingerprints in TiDB indefinitely to prevent lifetime duplicate applications.

