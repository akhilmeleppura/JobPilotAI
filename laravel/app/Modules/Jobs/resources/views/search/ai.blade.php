@extends('layouts.app')

@section('title', 'Natural Language AI Job Search')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-stars text-primary me-2"></i>Natural Language AI Job Search
            </h3>
            <p class="text-secondary small mb-0">
                Conversational intent parsing with multi-factor match scoring against your career role profiles.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small fw-bold text-muted">Scoring Persona:</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-badge text-primary me-1"></i>{{ $activeProfile?->name ?? 'Default Persona' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    @foreach($roleProfiles as $prof)
                        <li>
                            <a class="dropdown-item small {{ $activeProfile?->id === $prof->id ? 'active' : '' }}" 
                               href="{{ route('jobs.ai-search', ['prompt' => $prompt, 'role_profile_id' => $prof->id]) }}">
                                {{ $prof->name }} {{ $prof->is_default ? '(Default)' : '' }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-grid me-1"></i>Standard Job Grid
            </a>
        </div>
    </div>

    <!-- Conversational Search Card -->
    <div class="card shadow-sm border-0 border-top border-primary border-4 mb-4" style="border-radius: 14px;">
        <div class="card-body p-4">
            <form action="{{ route('jobs.ai-search') }}" method="GET">
                @if(request('role_profile_id'))
                    <input type="hidden" name="role_profile_id" value="{{ request('role_profile_id') }}">
                @endif
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Describe your ideal opportunity in natural conversational English:</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-chat-quote-fill"></i></span>
                        <input type="text" name="prompt" class="form-control border-start-0 ps-0" 
                               placeholder="e.g. Find senior remote Laravel backend jobs paying over $130k with TiDB or distributed SQL experience..." 
                               value="{{ $prompt }}" required>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-stars me-1"></i>Search with AI
                        </button>
                    </div>
                </div>

                <!-- Quick Prompt Presets -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted me-1">Quick Prompts:</span>
                    <button type="button" class="btn btn-xs btn-light border rounded-pill py-1 px-3 text-secondary" 
                            onclick="setPrompt('Find high-paying senior remote Laravel and PHP backend jobs over $130k')">
                        Senior Remote Laravel ($130k+)
                    </button>
                    <button type="button" class="btn btn-xs btn-light border rounded-pill py-1 px-3 text-secondary" 
                            onclick="setPrompt('Show me WordPress and WooCommerce custom plugin architect opportunities in US or Europe')">
                        WooCommerce Plugin Architect
                    </button>
                    <button type="button" class="btn btn-xs btn-light border rounded-pill py-1 px-3 text-secondary" 
                            onclick="setPrompt('Full stack web engineering positions using Laravel, Vue 3, and Tailwind CSS')">
                        Full Stack Web Engineer (Vue/React)
                    </button>
                    <button type="button" class="btn btn-xs btn-light border rounded-pill py-1 px-3 text-secondary" 
                            onclick="setPrompt('Remote freelance contract MVP delivery and legacy modernization projects')">
                        Contract MVP Consultant
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Parsed Search Intent Box (If query executed) -->
    @if($parsedParams)
        <div class="card shadow-sm border-0 mb-4 bg-light" style="border-radius: 12px;">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white"><i class="bi bi-cpu me-1"></i>AI Query Understanding</span>
                        <span class="small text-secondary">{{ $parsedParams['intent_summary'] }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @if($parsedParams['target_role'])
                            <span class="badge bg-secondary-subtle text-secondary border">Role: {{ $parsedParams['target_role'] }}</span>
                        @endif
                        @if($parsedParams['work_mode'] !== 'Any')
                            <span class="badge bg-success-subtle text-success border">Mode: {{ $parsedParams['work_mode'] }}</span>
                        @endif
                        @if($parsedParams['salary_min'])
                            <span class="badge bg-warning-subtle text-warning border">Min: ${{ number_format($parsedParams['salary_min']) }}</span>
                        @endif
                        @foreach($parsedParams['keywords'] as $kw)
                            <span class="badge bg-light text-dark border">{{ $kw }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Ranked Results Section -->
    @if(!empty($prompt))
        <div class="mb-4">
            <h5 class="fw-bold text-dark mb-3">
                Ranked Match Results ({{ $rankedResults->count() }})
            </h5>

            <div class="row g-3">
                @forelse($rankedResults as $job)
                    @php $eval = $job->match_evaluation; @endphp
                    <div class="col-12">
                        <div class="card shadow-sm border-0 hover-shadow" style="border-radius: 14px;">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <!-- Match Score Badge Column -->
                                    <div class="col-md-2 text-center border-end pe-md-3 mb-3 mb-md-0">
                                        <div class="d-inline-flex flex-column align-items-center justify-content-center p-3 rounded-circle {{ $eval['score'] >= 80 ? 'bg-success-subtle text-success border border-success-subtle' : ($eval['score'] >= 65 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle') }}" 
                                             style="width: 86px; height: 86px;">
                                            <span class="fs-4 fw-bold lh-1">{{ $eval['score'] }}%</span>
                                            <span class="small" style="font-size: 0.68rem;">MATCH</span>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge {{ $eval['score'] >= 80 ? 'bg-success' : ($eval['score'] >= 65 ? 'bg-primary' : 'bg-warning text-dark') }} rounded-pill" style="font-size: 0.72rem;">
                                                {{ $eval['grade'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Opportunity Details Column -->
                                    <div class="col-md-7 ps-md-4 mb-3 mb-md-0">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h5 class="fw-bold text-dark mb-0">{{ $job->job_title }}</h5>
                                            <span class="badge bg-light text-muted border" style="font-size: 0.75rem;">{{ $job->source_provider }}</span>
                                        </div>
                                        <div class="text-secondary small mb-2">
                                            <strong>{{ $job->company_name }}</strong> &bull; 
                                            <span class="badge bg-success-subtle text-success">{{ $job->work_mode }}</span> &bull; 
                                            {{ $job->location }}
                                            @if($job->salary_min)
                                                &bull; <span class="fw-bold text-dark">{{ $job->currency }} {{ number_format($job->salary_min) }}{{ $job->salary_max ? ' - ' . number_format($job->salary_max) : '+' }}</span>
                                            @endif
                                        </div>

                                        <!-- Matched vs Missing Skills -->
                                        <div class="mb-2">
                                            @if(!empty($eval['matched_skills']))
                                                <div class="small text-muted mb-1">
                                                    <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Skills Matched:</span>
                                                    @foreach($eval['matched_skills'] as $ms)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle me-1">{{ $ms }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if(!empty($eval['missing_skills']))
                                                <div class="small text-muted">
                                                    <span class="text-warning fw-bold"><i class="bi bi-exclamation-circle me-1"></i>Skill Gaps:</span>
                                                    @foreach($eval['missing_skills'] as $mis)
                                                        <span class="badge bg-light text-secondary border me-1">{{ $mis }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Reasoning Highlights -->
                                        <ul class="small text-secondary mb-0 ps-3" style="font-size: 0.8rem;">
                                            @foreach(array_slice($eval['pros'], 0, 2) as $pro)
                                                <li>{{ $pro }}</li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <!-- Quick Staging & Actions Column -->
                                    <div class="col-md-3 text-end ps-md-3 border-start">
                                        <div class="small text-muted mb-2 text-start" style="font-size: 0.75rem;">
                                            {{ $eval['recommendation'] }}
                                        </div>

                                        <div class="d-grid gap-2">
                                            @if($job->status !== 'Staged' && $job->status !== 'Applied')
                                                <form action="{{ route('jobs.stage', $job->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                                        <i class="bi bi-plus-circle me-1"></i>Stage Application
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('applications.index') }}" class="btn btn-sm btn-outline-info w-100">
                                                    <i class="bi bi-check2-circle me-1"></i>View in Registry
                                                </a>
                                            @endif

                                            <div class="d-flex gap-1">
                                                <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary w-50">
                                                    Details
                                                </a>
                                                @if($job->apply_url)
                                                    <a href="{{ $job->apply_url }}" target="_blank" class="btn btn-sm btn-outline-dark w-50">
                                                        Apply <i class="bi bi-box-arrow-up-right"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card shadow-sm border-0 p-5 text-center text-muted" style="border-radius: 14px;">
                            <i class="bi bi-search fs-1 text-secondary mb-3"></i>
                            <h5 class="fw-bold text-dark">No opportunities found matching this search criteria</h5>
                            <p class="small text-secondary mb-3">
                                Try relaxing your salary threshold, switching location, or fetching new live vacancies.
                            </p>
                            <div>
                                <form action="{{ route('jobs.fetch') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-cloud-arrow-down-fill me-1"></i>Fetch Fresh Openings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Empty State Prompt Helper -->
        <div class="card shadow-sm border-0 p-5 text-center bg-white" style="border-radius: 14px;">
            <i class="bi bi-stars fs-1 text-primary mb-3"></i>
            <h4 class="fw-bold text-dark">Discover Jobs Using Conversational AI</h4>
            <p class="text-secondary small col-md-6 mx-auto mb-4">
                Ask naturally about roles, tech stacks, salary expectations, and working arrangements. JobPilot AI parses your prompt, matches requirements against your personas, and ranks the highest-conviction openings.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('jobs.ai-search', ['prompt' => 'Find high-paying remote Laravel & PHP backend jobs over $130k']) }}" class="btn btn-sm btn-outline-primary">
                    Try Example Search: "Remote Laravel $130k+"
                </a>
            </div>
        </div>
    @endif
</div>

<script>
function setPrompt(text) {
    document.querySelector('input[name="prompt"]').value = text;
    document.querySelector('form').submit();
}
</script>
@endsection

