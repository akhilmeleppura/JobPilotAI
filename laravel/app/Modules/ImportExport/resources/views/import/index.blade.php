@extends('layouts.app')

@section('title', 'Spreadsheet & Document Import Studio')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-file-earmark-spreadsheet-fill text-primary me-2"></i>Spreadsheet & Document Import Studio
            </h3>
            <p class="text-secondary small mb-0">
                Bulk ingest historical job applications via <strong>CSV</strong>, <strong>XLSX</strong>, <strong>Google Sheets</strong>, or <strong>Email confirmation snippets</strong> with automated SHA-256 deduplication.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('import.template') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-download"></i>
                <span>Download Sample CSV</span>
            </a>
            <a href="{{ route('applications.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-journal-check me-1"></i>View Application Registry
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="stat-card">
                <div class="text-muted small fw-semibold">TOTAL IMPORTED APPLICATIONS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_imported']) }} Applications</div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="stat-card">
                <div class="text-primary small fw-semibold">IMPORT BATCHES PROCESSED</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_batches']) }} Batches</div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="stat-card">
                <div class="text-warning small fw-semibold">DUPLICATES PREVENTED</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_duplicates']) }} Duplicates</div>
            </div>
        </div>
    </div>

    <!-- Multi-Format Import Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <ul class="nav nav-tabs card-header-tabs" id="importTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold" id="file-tab" data-bs-toggle="tab" data-bs-target="#filePane" type="button" role="tab">
                        <i class="bi bi-file-earmark-arrow-up me-1 text-primary"></i>CSV / XLSX File Upload
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold" id="sheets-tab" data-bs-toggle="tab" data-bs-target="#sheetsPane" type="button" role="tab">
                        <i class="bi bi-google me-1 text-success"></i>Google Sheets Sync
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold" id="text-tab" data-bs-toggle="tab" data-bs-target="#textPane" type="button" role="tab">
                        <i class="bi bi-chat-left-text me-1 text-info"></i>Email Confirmation / Text
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="importTabsContent">
                <!-- Tab 1: File Upload -->
                <div class="tab-pane fade show active" id="filePane" role="tabpanel">
                    <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="p-4 border-2 border-dashed rounded text-center bg-light mb-3" style="border-style: dashed; border-color: #cbd5e1;">
                            <i class="bi bi-cloud-arrow-up fs-1 text-primary mb-2 d-block"></i>
                            <h6 class="fw-bold text-dark">Upload Spreadsheet File</h6>
                            <p class="small text-muted mb-3">Accepts standard <code>.csv</code> or <code>.xlsx</code> spreadsheets up to 10MB.</p>
                            <div class="d-inline-block">
                                <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.xlsx" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check form-check-sm">
                                <input class="form-check-input" type="checkbox" name="review_mapping" id="reviewMappingCheck" value="1">
                                <label class="form-check-label small text-secondary" for="reviewMappingCheck">
                                    Always preview and confirm column mapping before importing
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-box-arrow-in-down me-1"></i>Upload & Ingest
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab 2: Google Sheets URL -->
                <div class="tab-pane fade" id="sheetsPane" role="tabpanel">
                    <form action="{{ route('import.upload') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Google Sheet Shareable Link</label>
                            <input type="url" name="google_sheets_url" class="form-control" placeholder="https://docs.google.com/spreadsheets/d/your-sheet-id/edit#gid=0" required>
                            <div class="form-text small text-muted">
                                <i class="bi bi-info-circle me-1"></i>Ensure link sharing is set to <strong>"Anyone with the link can view"</strong> so JobPilot AI can parse the sheet.
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-sm px-4">
                                <i class="bi bi-arrow-repeat me-1"></i>Fetch & Ingest Google Sheet
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab 3: Text Snippet -->
                <div class="tab-pane fade" id="textPane" role="tabpanel">
                    <form action="{{ route('import.upload') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Paste Job Confirmation or Email Text</label>
                            <textarea name="raw_text" rows="5" class="form-control font-monospace small" placeholder="Company: Stripe&#10;Position: Senior Backend Engineer&#10;Status: Applied&#10;Date: 2026-10-24&#10;URL: https://stripe.com/jobs/123" required></textarea>
                            <div class="form-text small text-muted">
                                Our AI parser automatically recognizes Company, Position, Status, Date, and URLs.
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-info btn-sm text-white px-4">
                                <i class="bi bi-cpu me-1"></i>Parse & Register Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Import Batches Table -->
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Ingestion Batches</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Batch ID / Filename</th>
                            <th>Format</th>
                            <th>Total Rows</th>
                            <th>Imported</th>
                            <th>Duplicates</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr>
                                <td class="ps-4 py-3">
                                    <span class="fw-bold text-dark">#{{ $batch->id }}</span>
                                    <span class="text-secondary ms-2">{{ $batch->filename }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $batch->file_type }}</span>
                                </td>
                                <td>{{ $batch->total_rows }}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        +{{ $batch->imported_count }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        {{ $batch->duplicates_count }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $batch->status === 'Completed' ? 'bg-success' : 'bg-danger' }}">
                                        {{ $batch->status }}
                                    </span>
                                </td>
                                <td class="text-end pe-4 small text-muted">
                                    {{ $batch->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No spreadsheets or documents imported yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($batches->hasPages())
            <div class="card-footer bg-white py-2 px-4 border-top">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

