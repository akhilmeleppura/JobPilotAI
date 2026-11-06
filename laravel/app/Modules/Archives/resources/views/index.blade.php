@extends('layouts.app')

@section('title', 'Google Drive & Monthly Sheets Archives')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i>Multi-Tier Long-Term Archive
            </h3>
            <p class="text-secondary small mb-0">
                Tier 1: TiDB Cloud (Hot/Registry) &bull; Tier 2: Google Drive JSONL.gz (Cold/Permanent) &bull; Tier 3: Google Sheets (Human-Readable Overview).
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ $errors->first('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Generate Monthly Archive Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <h5 class="fw-bold text-dark mb-1">Generate Monthly Archive Package</h5>
                    <p class="text-secondary small mb-0">
                        Packages all applications and event logs into a compressed <code>.jsonl.gz</code> file and CSV spreadsheet with SHA-256 integrity verification.
                    </p>
                </div>
                <div class="col-lg-6">
                    <form action="{{ route('archives.generate') }}" method="POST" class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        @csrf
                        <div style="width: 140px;">
                            <select name="month" class="form-select form-select-sm" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div style="width: 100px;">
                            <input type="number" name="year" class="form-control form-control-sm" value="{{ $currentYear }}" min="2020" max="2035" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-file-earmark-zip"></i>
                            <span>Compile Archive</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Archives History Table -->
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header bg-white border-0 pt-3 px-4">
            <h5 class="fw-bold text-dark mb-0">Archived Monthly Partitions</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase" style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-4">Period</th>
                            <th>Records Count</th>
                            <th>Package Size</th>
                            <th>SHA-256 Checksum</th>
                            <th>Cloud Sync Status</th>
                            <th>Archived At</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($archives as $archive)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $archive->formatted_period }}</div>
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                        {{ $archive->archive_year }}/{{ str_pad($archive->archive_month, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 fw-semibold px-2">
                                        {{ number_format($archive->records_count) }} apps
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-secondary">{{ round($archive->file_size_bytes / 1024, 2) }} KB</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <code class="text-primary small" title="{{ $archive->jsonl_checksum_sha256 }}">
                                            {{ substr($archive->jsonl_checksum_sha256, 0, 12) }}...
                                        </code>
                                        <form action="{{ route('archives.verify-checksum', $archive->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none text-muted" title="Verify Checksum">
                                                <i class="bi bi-shield-check text-success"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    @if($archive->status === 'Synced' || $archive->status === 'Uploaded')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                            <i class="bi bi-google me-1"></i>Drive & Sheets Synced
                                        </span>
                                    @elseif($archive->status === 'Verified')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">
                                            <i class="bi bi-check2-circle me-1"></i>Integrity Verified
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">
                                            <i class="bi bi-clock me-1"></i>Local Staging Ready
                                        </span>
                                    @endif
                                </td>
                                <td class="small text-secondary">
                                    {{ $archive->archived_at->format('M d, Y H:i') }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('archives.download-jsonl', $archive->id) }}" class="btn btn-sm btn-outline-primary" title="Download JSONL.gz">
                                            <i class="bi bi-file-earmark-zip me-1"></i>JSONL.gz
                                        </a>
                                        <a href="{{ route('archives.download-csv', $archive->id) }}" class="btn btn-sm btn-outline-secondary" title="Download CSV">
                                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV
                                        </a>
                                        <form action="{{ route('archives.sync-drive', $archive->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light border" title="Sync to Google Drive / Sheets">
                                                <i class="bi bi-cloud-upload text-primary"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-cloud-arrow-up" style="font-size: 2.5rem;"></i>
                                    <p class="mt-2 mb-0">No monthly archives generated yet.</p>
                                    <p class="small text-secondary">Select a month above and click "Compile Archive" to create your first package.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

