@extends('layouts.app')

@section('title', 'LinkedIn Profile Optimizer')

@section('content')
<div class="container-fluid px-0">
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-linkedin text-primary me-2"></i>LinkedIn Profile Optimizer
            </h3>
            <p class="text-secondary small mb-0">
                Recruiter keyword gap analysis, high-CTR headlines, and Google XYZ achievement restructuring.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-shield-check text-success me-1"></i>Zero-Password Safe (No Bot Scraping)
            </span>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="runLiveAudit()">
                <i class="bi bi-arrow-repeat me-1"></i>Run Live Audit
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('profile-save-form').submit()">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i>Save Profile
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Role Persona Switcher Tabs -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold text-muted text-uppercase me-2"><i class="bi bi-person-badge me-1"></i>Target Persona:</span>
                    <ul class="nav nav-pills gap-1" id="personaTabs">
                        @foreach($profiles as $prof)
                            <li class="nav-item">
                                <a class="nav-link py-1 px-3 small {{ $selectedProfile?->id === $prof->id ? 'active' : '' }}" 
                                   href="{{ route('linkedin.optimizer', ['role_profile_id' => $prof->id]) }}">
                                    {{ $prof->name }}
                                    @if($prof->is_default)
                                        <span class="badge bg-light text-dark ms-1">Default</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="small text-muted">
                    <i class="bi bi-info-circle me-1"></i>Audits evaluate against this persona's target titles & technical skills.
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Left Audit/Scoring, Right Optimization Workbench -->
    <div class="row g-4 mb-4">
        <!-- Left: Recruiter Score & Keyword Gap -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="border-radius: 14px; top: 90px;">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-speedometer2 text-primary me-2"></i>Recruiter Search Score
                        </h6>
                        <span class="badge {{ $audit['recruiter_score'] >= 80 ? 'bg-success' : ($audit['recruiter_score'] >= 60 ? 'bg-warning text-dark' : 'bg-danger') }} px-2 py-1" id="score-badge">
                            {{ $audit['recruiter_score'] }}/100
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Gauge Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Recruiter Discoverability</span>
                            <span class="fw-bold text-dark" id="score-percentage">{{ $audit['recruiter_score'] }}%</span>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 6px;">
                            <div class="progress-bar {{ $audit['recruiter_score'] >= 80 ? 'bg-success' : ($audit['recruiter_score'] >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                                 id="score-progress"
                                 role="progressbar" 
                                 style="width: {{ $audit['recruiter_score'] }}%"></div>
                        </div>
                    </div>

                    <!-- Missing Keywords (Recruiter Gaps) -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-danger d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-exclamation-triangle-fill me-1"></i>Missing Search Keywords</span>
                            <span class="badge bg-danger-subtle text-danger" id="missing-count">{{ count($audit['missing_keywords']) }}</span>
                        </label>
                        <div class="p-2 border rounded bg-light" style="min-height: 70px; max-height: 150px; overflow-y: auto;" id="missing-keywords-container">
                            @forelse($audit['missing_keywords'] as $kw)
                                <span class="badge bg-white text-danger border border-danger-subtle me-1 mb-1 shadow-xs cursor-pointer" 
                                      title="Click to copy" onclick="copyText('{{ $kw }}')">
                                    + {{ $kw }}
                                </span>
                            @empty
                                <span class="small text-success"><i class="bi bi-check-circle me-1"></i>Zero critical keyword gaps detected!</span>
                            @endforelse
                        </div>
                        <div class="form-text small text-muted">Recruiter searches filter strictly by these keywords.</div>
                    </div>

                    <!-- Matched Keywords -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-success d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-check2-circle me-1"></i>Matched Keywords</span>
                            <span class="badge bg-success-subtle text-success" id="matched-count">{{ count($audit['matched_keywords']) }}</span>
                        </label>
                        <div class="p-2 border rounded bg-light" style="max-height: 120px; overflow-y: auto;" id="matched-keywords-container">
                            @forelse($audit['matched_keywords'] as $kw)
                                <span class="badge bg-success text-white me-1 mb-1">
                                    <i class="bi bi-check"></i>{{ $kw }}
                                </span>
                            @empty
                                <span class="small text-muted">No target keywords matched yet.</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div>
                        <h6 class="small fw-bold text-dark mb-2"><i class="bi bi-lightbulb-fill text-warning me-1"></i>Actionable Fixes</h6>
                        <ul class="list-unstyled small mb-0" id="suggestions-list">
                            @forelse($audit['suggestions'] as $suggestion)
                                <li class="d-flex align-items-start gap-2 mb-2 text-secondary">
                                    <i class="bi bi-arrow-right-short text-primary mt-1"></i>
                                    <span>{{ $suggestion }}</span>
                                </li>
                            @empty
                                <li class="text-success small"><i class="bi bi-star-fill text-warning me-1"></i>Your profile copy is highly optimized for technical recruiters!</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Optimization Workbench & Form -->
        <div class="col-lg-8">
            <form id="profile-save-form" action="{{ route('linkedin.save') }}" method="POST">
                @csrf
                <input type="hidden" name="role_profile_id" id="form-role-profile-id" value="{{ $selectedProfile?->id }}">

                <!-- Card 1: Headline Studio -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-card-heading text-primary me-2"></i>Headline Studio
                            </h6>
                            <small class="text-muted">Maximum 220 characters. Shown across search results and connection requests.</small>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="generateComponent('headlines')">
                            <i class="bi bi-stars me-1"></i>Generate AI Variations
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <!-- Active Headline Input -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Active / Optimized Headline:</label>
                                <span class="small text-muted" id="headline-counter">0 / 220</span>
                            </div>
                            <div class="input-group">
                                <input type="text" name="optimized_headline" id="optimized_headline" 
                                       class="form-control" maxlength="220"
                                       placeholder="e.g. Senior Laravel Architect | High-Throughput APIs | Microservices | MySQL"
                                       value="{{ old('optimized_headline', $profile->optimized_headline ?: $profile->current_headline) }}"
                                       oninput="updateHeadlineCounter()">
                                <button type="button" class="btn btn-outline-secondary" onclick="copyInput('optimized_headline')">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <!-- 4 AI Variation Cards -->
                        <h6 class="small fw-bold text-uppercase text-muted mb-2">High-Converting Headline Variations:</h6>
                        <div class="row g-3" id="headline-variations-row">
                            @php
                                $variations = $profile->headline_variations ?? [];
                                $varStyles = [
                                    'authority' => ['label' => 'Impact & Authority', 'icon' => 'bi-award-fill', 'color' => 'primary'],
                                    'keyword' => ['label' => 'Keyword-Dense (Boolean SEO)', 'icon' => 'bi-search', 'color' => 'info'],
                                    'metrics' => ['label' => 'Quantified Metrics', 'icon' => 'bi-graph-up-arrow', 'color' => 'success'],
                                    'consulting' => ['label' => 'Advisory & Modernization', 'icon' => 'bi-briefcase-fill', 'color' => 'dark'],
                                ];
                            @endphp

                            @foreach($varStyles as $key => $style)
                                <div class="col-md-6">
                                    <div class="card h-100 border bg-light shadow-xs p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-{{ $style['color'] }}-subtle text-{{ $style['color'] }} small">
                                                <i class="bi {{ $style['icon'] }} me-1"></i>{{ $style['label'] }}
                                            </span>
                                            <span class="small text-muted var-len" id="len-{{ $key }}">
                                                {{ isset($variations[$key]) ? mb_strlen($variations[$key]) : 0 }} chars
                                            </span>
                                        </div>
                                        <p class="small text-dark fw-medium mb-3 var-text" id="var-{{ $key }}" style="min-height: 48px;">
                                            {{ $variations[$key] ?? 'Click "Generate AI Variations" to craft this tailored angle.' }}
                                        </p>
                                        <div class="mt-auto d-flex gap-2">
                                            <button type="button" class="btn btn-white btn-sm border flex-grow-1" 
                                                    onclick="applyHeadline('var-{{ $key }}')">
                                                <i class="bi bi-check2 me-1"></i>Use This
                                            </button>
                                            <button type="button" class="btn btn-light btn-sm border" 
                                                    onclick="copyTextFromElement('var-{{ $key }}')">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Card 2: About / Summary Section -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-person-lines-fill text-primary me-2"></i>About & Summary Section
                            </h6>
                            <small class="text-muted">Maximum 2,600 characters. First 3 lines (~200 chars) are crucial before "...see more".</small>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="generateComponent('about')">
                            <i class="bi bi-stars me-1"></i>Generate AI About
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <!-- Hook Preview ("Above the fold") -->
                        <div class="alert alert-light border mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-dark"><i class="bi bi-eye-fill text-primary me-1"></i>Above-the-Fold Hook Preview:</span>
                                <span class="badge bg-secondary-subtle text-secondary small">First 200 Characters</span>
                            </div>
                            <div class="small text-secondary fst-italic" id="hook-preview">
                                {{ Str::limit(old('optimized_about', $profile->optimized_about ?: $profile->current_about), 200, '... [see more]') ?: 'Start typing or generate an About section to preview your recruiter hook.' }}
                            </div>
                        </div>

                        <!-- Full About Textarea -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Professional Summary Copy:</label>
                                <span class="small text-muted" id="about-counter">0 / 2600</span>
                            </div>
                            <textarea name="optimized_about" id="optimized_about" rows="10" 
                                      class="form-control font-monospace small" maxlength="2600"
                                      placeholder="Write or generate your professional narrative, technical arsenal, and quantifiable business achievements..."
                                      oninput="updateAboutCounter()">{{ old('optimized_about', $profile->optimized_about ?: $profile->current_about) }}</textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyInput('optimized_about')">
                                <i class="bi bi-clipboard me-1"></i>Copy About Section
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Experience Restructuring (Google XYZ Formula) -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-check2-square text-primary me-2"></i>Experience Bullets (Google XYZ Framework)
                            </h6>
                            <small class="text-muted">Structure: Accomplished [X], as measured by [Y], by doing [Z].</small>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="transformBulletsAjax()">
                            <i class="bi bi-magic me-1"></i>Transform to Google XYZ
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Raw Bullet Points / Duties:</label>
                                <textarea name="current_experience" id="current_experience" rows="8" 
                                          class="form-control small font-monospace"
                                          placeholder="Paste unpolished responsibilities or bullet points here...
e.g.
- Responsible for backend APIs and database queries
- Worked on optimizing site speed
- Helped build payment gateway integration">{{ old('current_experience', $profile->current_experience) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-success">Optimized XYZ Achievements:</label>
                                <textarea name="optimized_experience" id="optimized_experience" rows="8" 
                                          class="form-control small font-monospace bg-light"
                                          placeholder="Optimized high-impact bullets with strong action verbs and quantified metrics will appear here...">{{ old('optimized_experience', $profile->optimized_experience) }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyInput('optimized_experience')">
                                <i class="bi bi-clipboard me-1"></i>Copy Optimized Bullets
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save & Strategy Bar -->
                <div class="card shadow-sm border-0 mb-4 bg-light" style="border-radius: 14px;">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Ready to refresh your LinkedIn Profile?</h6>
                            <p class="text-secondary small mb-0">Changes are saved securely into your local JobPilot AI database.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="generateComponent('all')">
                                <i class="bi bi-stars me-1"></i>Regenerate Everything
                            </button>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-check-circle-fill me-1"></i>Save All Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live Character Counters
    function updateHeadlineCounter() {
        const input = document.getElementById('optimized_headline');
        const counter = document.getElementById('headline-counter');
        const len = input.value.length;
        counter.textContent = `${len} / 220`;
        counter.className = len > 220 ? 'small text-danger fw-bold' : 'small text-muted';
    }

    function updateAboutCounter() {
        const input = document.getElementById('optimized_about');
        const counter = document.getElementById('about-counter');
        const hook = document.getElementById('hook-preview');
        const text = input.value;
        const len = text.length;

        counter.textContent = `${len} / 2600`;
        counter.className = len > 2600 ? 'small text-danger fw-bold' : 'small text-muted';

        if (len > 0) {
            hook.textContent = text.substring(0, 200) + (len > 200 ? '... [see more]' : '');
        } else {
            hook.textContent = 'Start typing or generate an About section to preview your recruiter hook.';
        }
    }

    // Initialize Counters
    document.addEventListener('DOMContentLoaded', () => {
        updateHeadlineCounter();
        updateAboutCounter();
    });

    // Copy Helpers
    function copyText(str) {
        navigator.clipboard.writeText(str).then(() => {
            alert(`Copied "${str}" to clipboard!`);
        });
    }

    function copyInput(elementId) {
        const el = document.getElementById(elementId);
        navigator.clipboard.writeText(el.value).then(() => {
            alert('Content copied to clipboard!');
        });
    }

    function copyTextFromElement(elementId) {
        const el = document.getElementById(elementId);
        navigator.clipboard.writeText(el.textContent.trim()).then(() => {
            alert('Headline variation copied to clipboard!');
        });
    }

    function applyHeadline(elementId) {
        const el = document.getElementById(elementId);
        const headlineInput = document.getElementById('optimized_headline');
        headlineInput.value = el.textContent.trim();
        updateHeadlineCounter();
        runLiveAudit();
    }

    // Live Audit via AJAX
    function runLiveAudit() {
        const roleProfileId = document.getElementById('form-role-profile-id').value;
        const headline = document.getElementById('optimized_headline').value;
        const about = document.getElementById('optimized_about').value;
        const experience = document.getElementById('optimized_experience').value || document.getElementById('current_experience').value;

        fetch('{{ route("linkedin.audit") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                role_profile_id: roleProfileId,
                headline: headline,
                about: about,
                experience: experience
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.audit) {
                renderAudit(data.audit);
            }
        })
        .catch(err => console.error('Audit failed:', err));
    }

    function renderAudit(audit) {
        // Score badge & progress
        const scoreBadge = document.getElementById('score-badge');
        const scorePct = document.getElementById('score-percentage');
        const progress = document.getElementById('score-progress');

        scoreBadge.textContent = `${audit.recruiter_score}/100`;
        scorePct.textContent = `${audit.recruiter_score}%`;
        progress.style.width = `${audit.recruiter_score}%`;

        const badgeClass = audit.recruiter_score >= 80 ? 'bg-success' : (audit.recruiter_score >= 60 ? 'bg-warning text-dark' : 'bg-danger');
        scoreBadge.className = `badge ${badgeClass} px-2 py-1`;
        progress.className = `progress-bar ${audit.recruiter_score >= 80 ? 'bg-success' : (audit.recruiter_score >= 60 ? 'bg-warning' : 'bg-danger')}`;

        // Missing keywords
        document.getElementById('missing-count').textContent = audit.missing_keywords.length;
        const missingContainer = document.getElementById('missing-keywords-container');
        if (audit.missing_keywords.length === 0) {
            missingContainer.innerHTML = '<span class="small text-success"><i class="bi bi-check-circle me-1"></i>Zero critical keyword gaps detected!</span>';
        } else {
            missingContainer.innerHTML = audit.missing_keywords.map(kw => 
                `<span class="badge bg-white text-danger border border-danger-subtle me-1 mb-1 shadow-xs cursor-pointer" onclick="copyText('${kw}')">+ ${kw}</span>`
            ).join('');
        }

        // Matched keywords
        document.getElementById('matched-count').textContent = audit.matched_keywords.length;
        const matchedContainer = document.getElementById('matched-keywords-container');
        if (audit.matched_keywords.length === 0) {
            matchedContainer.innerHTML = '<span class="small text-muted">No target keywords matched yet.</span>';
        } else {
            matchedContainer.innerHTML = audit.matched_keywords.map(kw => 
                `<span class="badge bg-success text-white me-1 mb-1"><i class="bi bi-check"></i>${kw}</span>`
            ).join('');
        }

        // Suggestions
        const suggestionsList = document.getElementById('suggestions-list');
        if (audit.suggestions.length === 0) {
            suggestionsList.innerHTML = '<li class="text-success small"><i class="bi bi-star-fill text-warning me-1"></i>Your profile copy is highly optimized for technical recruiters!</li>';
        } else {
            suggestionsList.innerHTML = audit.suggestions.map(s => 
                `<li class="d-flex align-items-start gap-2 mb-2 text-secondary"><i class="bi bi-arrow-right-short text-primary mt-1"></i><span>${s}</span></li>`
            ).join('');
        }
    }

    // Component Generation via AJAX
    function generateComponent(type) {
        const roleProfileId = document.getElementById('form-role-profile-id').value;
        const currentHeadline = document.getElementById('optimized_headline').value;
        const currentAbout = document.getElementById('optimized_about').value;
        const currentExperience = document.getElementById('current_experience').value;

        const btn = event?.currentTarget;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating...';
        }

        fetch('{{ route("linkedin.generate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: type,
                role_profile_id: roleProfileId,
                current_headline: currentHeadline,
                current_about: currentAbout,
                current_experience: currentExperience
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.headlines) {
                for (const [key, val] of Object.entries(data.headlines)) {
                    const el = document.getElementById(`var-${key}`);
                    const lenEl = document.getElementById(`len-${key}`);
                    if (el) el.textContent = val;
                    if (lenEl) lenEl.textContent = `${val.length} chars`;
                }
                if (data.headlines.authority) {
                    document.getElementById('optimized_headline').value = data.headlines.authority;
                    updateHeadlineCounter();
                }
            }

            if (data.about) {
                document.getElementById('optimized_about').value = data.about;
                updateAboutCounter();
            }

            if (data.experience) {
                document.getElementById('optimized_experience').value = data.experience;
            }

            runLiveAudit();
        })
        .catch(err => {
            console.error('Generation error:', err);
            alert('Failed to generate copy. Please try again.');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-stars me-1"></i>Generate AI Variations';
            }
        });
    }

    // Transform Experience Bullets via AJAX
    function transformBulletsAjax() {
        const raw = document.getElementById('current_experience').value;
        if (!raw.trim()) {
            alert('Please enter raw duties or bullets first.');
            return;
        }

        fetch('{{ route("linkedin.transform-bullets") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ raw_experience: raw })
        })
        .then(res => res.json())
        .then(data => {
            if (data.optimized) {
                document.getElementById('optimized_experience').value = data.optimized;
                runLiveAudit();
            }
        })
        .catch(err => console.error('Transformation error:', err));
    }
</script>
@endpush
@endsection

