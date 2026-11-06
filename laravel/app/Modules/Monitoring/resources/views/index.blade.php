@extends('layouts.app')

@section('title', 'System Health, Security & Backups')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-activity text-primary me-2"></i>System Health, Security &amp; Backups
            </h3>
            <p class="text-secondary small mb-0">
                Subsystem observability, automated database backups, zero-spend enforcement, and deployment readiness.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-shield-check text-success me-1"></i>FREE_RESOURCES_ONLY Enforced
            </span>
            <form action="{{ route('monitoring.backup') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                    <i class="bi bi-database-fill-down me-1"></i>Create Backup Now
                </button>
            </form>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 1. Overall System Health Hero Card -->
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge {{ $health['overall_score'] >= 90 ? 'bg-success' : ($health['overall_score'] >= 65 ? 'bg-warning text-dark' : 'bg-danger') }} px-3 py-2 fs-6">
                            <i class="bi bi-check-circle-fill me-1"></i>{{ $health['overall_status'] }}
                        </span>
                        <h4 class="fw-bold text-dark mb-0">{{ $health['overall_score'] }}% Health Rating</h4>
                    </div>
                    <p class="text-secondary small mb-3">
                        All critical career data subsystems, encryption vaults, storage paths, and zero-spend AI limits evaluated at {{ date('M d, Y H:i:s', strtotime($health['timestamp'])) }}.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($health['subsystems'] as $key => $sub)
                            <span class="badge {{ $sub['status'] === 'healthy' ? 'bg-success-subtle text-success' : ($sub['status'] === 'warning' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }} small py-1 px-2 border">
                                <i class="bi {{ $sub['status'] === 'healthy' ? 'bi-check2' : 'bi-exclamation-circle' }} me-1"></i>{{ $sub['name'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="p-3 border rounded bg-light text-start d-inline-block w-100" style="max-width: 380px;">
                        <div class="small fw-bold text-dark mb-2"><i class="bi bi-cpu-fill text-primary me-1"></i>Runtime Environment:</div>
                        <ul class="list-unstyled small mb-0 text-secondary">
                            <li><strong>PHP:</strong> {{ PHP_VERSION }} ({{ PHP_OS }})</li>
                            <li><strong>Framework:</strong> Laravel {{ app()->version() }}</li>
                            <li><strong>Database:</strong> {{ config('database.default') }}</li>
                            <li><strong>Environment:</strong> {{ config('app.env') }} (Debug: {{ config('app.debug') ? 'True' : 'False' }})</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Subsystem Health Grid -->
    <div class="row g-3 mb-4">
        <!-- Database -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-dark"><i class="bi bi-database-check text-primary me-1"></i>Database</span>
                    <span class="badge {{ $health['subsystems']['database']['status'] === 'healthy' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} small">
                        {{ $health['subsystems']['database']['status'] }}
                    </span>
                </div>
                <h5 class="fw-bold mb-1 text-dark">{{ $health['subsystems']['database']['latency_ms'] ?? 0 }} ms</h5>
                <p class="small text-secondary mb-0">{{ $health['subsystems']['database']['details'] }}</p>
            </div>
        </div>

        <!-- Storage & Disk Space -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-dark"><i class="bi bi-hdd-fill text-info me-1"></i>Disk Space</span>
                    <span class="badge {{ $health['subsystems']['storage']['status'] === 'healthy' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} small">
                        {{ $health['subsystems']['storage']['percent_free'] }}% Free
                    </span>
                </div>
                <h5 class="fw-bold mb-1 text-dark">{{ $health['subsystems']['storage']['free_gb'] }} GB</h5>
                <p class="small text-secondary mb-0">Total: {{ $health['subsystems']['storage']['total_gb'] }} GB (Writable)</p>
            </div>
        </div>

        <!-- Zero-Spend AI Policy -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-dark"><i class="bi bi-stars text-warning me-1"></i>AI Gateway</span>
                    <span class="badge bg-success-subtle text-success small">Zero-Spend</span>
                </div>
                <h5 class="fw-bold mb-1 text-dark">{{ $health['subsystems']['ai_gateway']['configured_ai_providers'] }} Providers</h5>
                <p class="small text-secondary mb-0">Paid models blocked automatically</p>
            </div>
        </div>

        <!-- Security Hardening -->
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-dark"><i class="bi bi-shield-lock-fill text-success me-1"></i>Security</span>
                    <span class="badge bg-success-subtle text-success small">Hardened</span>
                </div>
                <h5 class="fw-bold mb-1 text-dark">Headers Active</h5>
                <p class="small text-secondary mb-0">nosniff, SAMEORIGIN, CSP</p>
            </div>
        </div>
    </div>

    <!-- 3. Automated Database Backups Hub -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-archive-fill text-primary me-2"></i>Database Backups &amp; Integrity Verification
                </h6>
                <small class="text-muted">Automated SQLite/TiDB backups with SHA-256 integrity checksums and 7-snapshot rotation.</small>
            </div>
            <span class="badge bg-primary-subtle text-primary">{{ $backups->count() }} Backups Retained</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Backup Filename</th>
                            <th>Database Engine</th>
                            <th>File Size</th>
                            <th>SHA-256 Checksum</th>
                            <th>Created Timestamp</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($backups as $backup)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-file-earmark-zip-fill text-primary me-1"></i>{{ $backup->filename }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        Applications: {{ $backup->metadata['applications_count'] ?? 0 }} • Resumes: {{ $backup->metadata['resumes_count'] ?? 0 }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border text-uppercase">{{ $backup->database_type }}</span>
                                </td>
                                <td>{{ $backup->formatted_size }}</td>
                                <td>
                                    <span class="font-monospace text-muted small" title="{{ $backup->checksum_sha256 }}" style="font-size: 0.75rem;">
                                        {{ substr($backup->checksum_sha256, 0, 16) }}...{{ substr($backup->checksum_sha256, -8) }}
                                    </span>
                                </td>
                                <td>{{ $backup->created_at->format('M d, Y H:i:s') }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('monitoring.backup.download', $backup->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-database-x fs-1 text-secondary d-block mb-2"></i>
                                    No database backups recorded yet. Click "Create Backup Now" to capture your first snapshot!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Oracle Cloud Always Free & Production Deployment Readiness -->
    <div class="card shadow-sm border-0 mb-4 bg-light" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-dark text-white px-2 py-1 small">Production Deployment</span>
                        <h5 class="fw-bold text-dark mb-0">Oracle Cloud Always Free VM (ARM64 Architecture)</h5>
                    </div>
                    <p class="text-secondary small mb-3">
                        JobPilot AI is engineered to run permanently on Oracle Cloud's Always Free Ampere A1 Compute (4 OCPUs, 24GB RAM, Ubuntu 24.04 LTS) connected to TiDB Cloud Starter over TLS.
                    </p>
                    <div class="row g-2 small">
                        <div class="col-sm-6">
                            <i class="bi bi-check2-circle text-success me-1"></i>Nginx HTTP/2 + Let's Encrypt SSL
                        </div>
                        <div class="col-sm-6">
                            <i class="bi bi-check2-circle text-success me-1"></i>Supervisor Queue Workers (`php artisan queue:work`)
                        </div>
                        <div class="col-sm-6">
                            <i class="bi bi-check2-circle text-success me-1"></i>Linux Cron Scheduler (`php artisan schedule:run`)
                        </div>
                        <div class="col-sm-6">
                            <i class="bi bi-check2-circle text-success me-1"></i>OPcache Preloading &amp; Zero-Cost Governance
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <span class="badge bg-success-subtle text-success p-3 border rounded-3 d-inline-block text-start">
                        <div class="fw-bold mb-1"><i class="bi bi-patch-check-fill me-1"></i>Production Ready</div>
                        <div class="small" style="font-size: 0.75rem;">Scripts located in <code>deploy/oracle_cloud_setup.sh</code></div>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

