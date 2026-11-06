@extends('layouts.app')

@section('title', 'Free AI Providers Gateway & Testing Playground')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-cpu-fill text-primary me-2"></i>Free-Only AI Gateway & Playground
            </h3>
            <p class="text-secondary small mb-0">
                Unified multi-model intelligence gateway strictly enforcing zero-cost free resources across Google AI Studio, Groq, OpenRouter, and local Ollama.
            </p>
        </div>
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-shield-check me-1"></i>Zero-Spend Enforcement ($0.00 Cost)
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

    @if($errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-shield-x fs-5"></i>
            <div>{{ $errors->first('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-muted small fw-semibold">TOTAL AI COMPLETIONS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_requests']) }} Requests</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-primary small fw-semibold">TOKENS PROCESSED</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_tokens']) }} Tokens</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-success small fw-semibold">LIFETIME AI SPEND</div>
                <div class="fs-4 fw-bold text-success mt-1">$0.000000 (Free Tier)</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-danger small fw-semibold">BLOCKED PAID ATTEMPTS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $metrics['blocked_paid_attempts'] }} Intercepted</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Interactive Playground -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h5 class="fw-bold text-dark mb-0">AI Completion Playground</h5>
                    <p class="text-secondary small mb-0">Test prompts across free models with automatic resilient fallback.</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('ai.generate') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">System Instructions (Optional)</label>
                            <textarea name="system_prompt" rows="2" class="form-control" placeholder="e.g. You are an expert ATS resume reviewer and technical career coach...">{{ old('system_prompt') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">User Prompt <span class="text-danger">*</span></label>
                            <textarea name="prompt" rows="4" class="form-control" placeholder="e.g. Extract the top 5 technical skills from this job vacancy and score them against a Senior Laravel Developer profile..." required>{{ old('prompt') }}</textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Preferred Free Provider</label>
                                <select name="provider" class="form-select form-select-sm">
                                    <option value="">Auto Fallback (Gemini &rarr; Groq &rarr; OpenRouter &rarr; Ollama)</option>
                                    <option value="gemini" {{ old('provider') === 'gemini' ? 'selected' : '' }}>Google AI Studio (Gemini 2.5 Flash)</option>
                                    <option value="groq" {{ old('provider') === 'groq' ? 'selected' : '' }}>Groq (Llama 3.3 70B)</option>
                                    <option value="openrouter" {{ old('provider') === 'openrouter' ? 'selected' : '' }}>OpenRouter (Free Tagged Tier)</option>
                                    <option value="ollama" {{ old('provider') === 'ollama' ? 'selected' : '' }}>Local Ollama (Private)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Temperature (Creativity)</label>
                                <input type="number" step="0.1" min="0" max="1.5" name="temperature" class="form-control form-control-sm" value="{{ old('temperature', 0.7) }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1 px-4">
                                <i class="bi bi-send-fill"></i>
                                <span>Execute Prompt</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Response Display Box -->
            @if(session('ai_response'))
                <div class="card shadow-sm border-0 border-start border-primary border-4 mb-4" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Model Output</h6>
                            @if(session('ai_meta'))
                                <div class="d-flex gap-1 flex-wrap">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ session('ai_meta')['provider'] }}</span>
                                    <span class="badge bg-light text-dark border">{{ session('ai_meta')['model'] }}</span>
                                    <span class="badge bg-secondary-subtle text-dark border">{{ session('ai_meta')['tokens'] }} tokens</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ session('ai_meta')['cost'] }}</span>
                                    <span class="badge bg-light text-muted border">{{ session('ai_meta')['duration'] }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap; font-family: system-ui, sans-serif; line-height: 1.6;">
                            {{ session('ai_response') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Available Free Providers Overview -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h6 class="fw-bold text-dark mb-0">Integrated Free Providers & Models</h6>
                </div>
                <div class="card-body p-4">
                    <div class="list-group list-group-flush">
                        @foreach($providers as $pKey => $provider)
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold mb-0 text-dark">{{ ucfirst($pKey) }}</h6>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Free Tier</span>
                                </div>
                                <div class="small text-muted mb-2">Verified Free Models:</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($provider->getAvailableFreeModels() as $mKey => $mLabel)
                                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">
                                            {{ $mKey }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent AI Request Logs -->
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h6 class="fw-bold text-dark mb-0">Recent Request Audit Trail</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-3">Provider / Model</th>
                                    <th>Tokens</th>
                                    <th>Latency</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark">{{ ucfirst($log->provider) }}</div>
                                            <div class="text-muted font-monospace" style="font-size: 0.7rem;">{{ Str::limit($log->model, 25) }}</div>
                                        </td>
                                        <td>{{ number_format($log->total_tokens) }}</td>
                                        <td>{{ $log->duration_ms }}ms</td>
                                        <td>
                                            @php
                                                $pill = match($log->status) {
                                                    'success' => 'bg-success-subtle text-success border-success-subtle',
                                                    'fallback_used' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                    'blocked_by_policy' => 'bg-danger text-white',
                                                    default => 'bg-danger-subtle text-danger border-danger-subtle'
                                                };
                                            @endphp
                                            <span class="badge border rounded-pill {{ $pill }}">
                                                {{ $log->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No requests executed yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

