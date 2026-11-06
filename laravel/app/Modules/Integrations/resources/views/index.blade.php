@extends('layouts.app')

@section('title', 'Integrations & Credential Vault')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-shield-lock-fill text-primary me-2"></i>Central Settings & Encrypted Credential Vault
            </h3>
            <p class="text-secondary small mb-0">
                All external API keys and OAuth tokens are encrypted at rest using AES-256-GCM (<code>APP_KEY</code>). Sensitive credentials are never exposed to client-side scripts.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-shield-check me-1"></i>FREE_RESOURCES_ONLY Active
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ session('error') ?? $errors->first() }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Health Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-muted small fw-semibold">TOTAL INTEGRATIONS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $metrics['total'] }} Providers</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-success small fw-semibold">HEALTHY & CONNECTED</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ $metrics['healthy'] }} Active</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-warning small fw-semibold">DEGRADED / SETUP NEEDED</div>
                <div class="fs-4 fw-bold text-warning mt-1">{{ $metrics['degraded'] }} Providers</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-primary small fw-semibold">RESOURCE GOVERNANCE</div>
                <div class="fs-4 fw-bold text-dark mt-1">Free-First Enforced</div>
            </div>
        </div>
    </div>

    <!-- Category Quick Navigation Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
        <a href="#all" class="btn btn-sm btn-outline-primary active rounded-pill px-3">All Categories</a>
        <a href="#ai" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-cpu me-1"></i>AI Providers</a>
        <a href="#email" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-envelope-at me-1"></i>Email Mailers</a>
        <a href="#cloud_storage" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-cloud-arrow-up me-1"></i>Cloud Storage</a>
        <a href="#job_sources" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-briefcase me-1"></i>Job Feeds</a>
        <a href="#system" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-shield-check me-1"></i>Free Policy Safeguards</a>
    </div>

    @php
        $groupTitles = [
            'ai' => ['title' => 'AI Intelligence Providers (Free-Only)', 'icon' => 'bi-cpu-fill'],
            'email' => ['title' => 'Email Outreach & Mailers', 'icon' => 'bi-envelope-at-fill'],
            'cloud_storage' => ['title' => 'Cloud Storage & Cold Archives', 'icon' => 'bi-cloud-arrow-up-fill'],
            'job_sources' => ['title' => 'Job Discovery Feeds & Engines', 'icon' => 'bi-briefcase-fill'],
            'system' => ['title' => 'System Governance & Quota Safeguards', 'icon' => 'bi-sliders2'],
        ];
    @endphp

    @foreach($groupTitles as $groupKey => $meta)
        @if(isset($grouped[$groupKey]))
            <div class="mb-4 pt-2" id="{{ $groupKey }}">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="bi {{ $meta['icon'] }} text-primary me-2"></i>{{ $meta['title'] }}
                </h5>

                <div class="row g-3">
                    @foreach($grouped[$groupKey] as $item)
                        <div class="col-xl-6 col-12">
                            <div class="card h-100 shadow-sm border-0" style="border-radius: 14px;">
                                <div class="card-body p-4 d-flex flex-column">
                                    <!-- Card Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">{{ $item->provider_name }}</h6>
                                            <span class="font-monospace small text-muted">{{ $item->provider_key }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @php
                                                $badgeClass = match($item->health_status) {
                                                    'Healthy' => 'bg-success-subtle text-success border-success-subtle',
                                                    'Degraded' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                    'Failed' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                    default => 'bg-light text-secondary border'
                                                };
                                            @endphp
                                            <span class="badge border rounded-pill {{ $badgeClass }}">
                                                {{ $item->health_status }}
                                            </span>
                                            <form action="{{ route('settings.toggle', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none" title="{{ $item->is_enabled ? 'Disable' : 'Enable' }}">
                                                    <i class="bi {{ $item->is_enabled ? 'bi-toggle-on text-primary fs-4' : 'bi-toggle-off text-muted fs-4' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Health message / note -->
                                    @if($item->health_message)
                                        <div class="p-2 rounded bg-light small text-secondary mb-3 border">
                                            <i class="bi bi-info-circle me-1 text-primary"></i>{{ $item->health_message }}
                                        </div>
                                    @endif

                                    <!-- Settings preview -->
                                    <div class="small text-secondary mb-3">
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">Configured Secrets:</span>
                                                <span class="font-monospace text-dark">{{ $item->getMaskedSecret('api_key') ?: $item->getMaskedSecret('access_token') }}</span>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">Daily Quota Ceiling:</span>
                                                <span class="fw-semibold text-dark">{{ $item->quota_limit_daily ? number_format($item->quota_limit_daily) . ' req/day' : 'Unlimited' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions & Edit Form Trigger -->
                                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                        <form action="{{ route('settings.ping', $item->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-activity me-1"></i>Test Ping
                                            </button>
                                        </form>

                                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editVault_{{ $item->id }}">
                                            <i class="bi bi-key-fill me-1"></i>Configure Credentials
                                        </button>
                                    </div>

                                    <!-- Collapse Edit Form -->
                                    <div class="collapse mt-3" id="editVault_{{ $item->id }}">
                                        <div class="p-3 bg-light rounded border">
                                            <form action="{{ route('settings.update', $item->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')

                                                @if($item->provider_group === 'ai')
                                                    @if($item->provider_key === 'ollama')
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Local Ollama Endpoint</label>
                                                            <input type="text" name="settings[endpoint_url]" class="form-control form-control-sm" value="{{ $item->settings['endpoint_url'] ?? 'http://127.0.0.1:11434' }}">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Default Ollama Model</label>
                                                            <input type="text" name="settings[default_model]" class="form-control form-control-sm" value="{{ $item->settings['default_model'] ?? 'llama3:latest' }}">
                                                        </div>
                                                    @else
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">API Key (AES-256 Encrypted)</label>
                                                            <input type="password" name="secrets[api_key]" class="form-control form-control-sm" placeholder="Paste new key (leave empty to retain)">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Default Free Model</label>
                                                            <input type="text" name="settings[default_model]" class="form-control form-control-sm" value="{{ $item->settings['default_model'] ?? '' }}">
                                                        </div>
                                                    @endif
                                                @elseif($item->provider_group === 'email')
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-semibold">OAuth Token / Refresh Token</label>
                                                        <input type="password" name="secrets[refresh_token]" class="form-control form-control-sm" placeholder="OAuth refresh token">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-semibold">Optional Custom Safety Cap</label>
                                                        <input type="number" name="quota_limit_daily" class="form-control form-control-sm" placeholder="Disabled (Uncapped)" value="{{ $item->quota_limit_daily }}">
                                                    </div>
                                                @elseif($item->provider_group === 'job_sources')
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-semibold">API Key / App Secret</label>
                                                        <input type="password" name="secrets[api_key]" class="form-control form-control-sm" placeholder="API Key">
                                                    </div>
                                                    @if($item->provider_key === 'adzuna')
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold">Adzuna App ID</label>
                                                            <input type="text" name="secrets[app_id]" class="form-control form-control-sm" placeholder="App ID">
                                                        </div>
                                                    @endif
                                                @elseif($item->provider_key === 'policy_governance')
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" name="settings[FREE_RESOURCES_ONLY]" value="1" id="freeCheck_{{ $item->id }}" {{ ($item->settings['FREE_RESOURCES_ONLY'] ?? true) ? 'checked' : '' }}>
                                                        <label class="form-check-label small fw-semibold" for="freeCheck_{{ $item->id }}">Strict FREE_RESOURCES_ONLY Enforcement</label>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-semibold">Optional User Safety Ceiling</label>
                                                        <input type="number" name="quota_limit_daily" class="form-control form-control-sm" placeholder="Disabled (Uncapped)" value="{{ $item->quota_limit_daily }}">
                                                    </div>
                                                @else
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-semibold">Access Token / Key</label>
                                                        <input type="password" name="secrets[access_token]" class="form-control form-control-sm" placeholder="Paste credential">
                                                    </div>
                                                @endif

                                                <div class="d-flex justify-content-end gap-2 mt-3">
                                                    <button type="submit" class="btn btn-sm btn-primary">Save to Vault</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection

