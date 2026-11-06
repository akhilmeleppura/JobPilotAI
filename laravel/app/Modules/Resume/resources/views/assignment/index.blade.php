@extends('layouts.app')

@section('title', 'Global CV Assignment Manager')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-file-earmark-person-fill text-primary me-2"></i>Global CV Assignment Manager
            </h3>
            <p class="text-secondary small mb-0">
                Multi-mode resume resolution pipeline: <strong>Mode 1</strong> (Global Default) &bull; <strong>Mode 2</strong> (Role Profile Linked) &bull; <strong>Mode 3</strong> (Application Override) &bull; <strong>Mode 4</strong> (AI Semantic Keyword Match).
            </p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-shield-check me-1"></i>ATS Truth Integrity Mandate Active
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

    <!-- Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-muted small fw-semibold">TOTAL TAILORED RESUMES</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $metrics['total_resumes'] }} Master CVs</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-primary small fw-semibold">GLOBAL DEFAULT (MODE 1)</div>
                <div class="fs-5 fw-bold text-dark mt-1 text-truncate" title="{{ $metrics['global_default'] }}">
                    {{ $metrics['global_default'] }}
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-success small fw-semibold">ROLE-MAPPED PERSONAS (MODE 2)</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ $metrics['role_mapped_count'] }} Linked</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-warning small fw-semibold">AVERAGE ATS READINESS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $metrics['avg_ats_score'] }}% Score</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Resumes List & Assignment Mapping -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-3 px-4">
                    <h5 class="fw-bold text-dark mb-0">Configured Resumes & Mode Assignments</h5>
                    <p class="text-secondary small mb-0">Map each resume to a career role profile or designate your primary global default.</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase" style="font-size: 0.75rem;">
                                <tr>
                                    <th class="ps-4">Resume Title & Stack</th>
                                    <th>Target Role Profile (Mode 2)</th>
                                    <th>ATS Score</th>
                                    <th>Assignment Mode</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($resumes as $res)
                                    <tr class="{{ $res->is_global_default ? 'table-light' : '' }}">
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark">{{ $res->title }}</div>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @foreach(array_slice($res->primary_skills ?? [], 0, 4) as $skill)
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">{{ $skill }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <form action="{{ route('resume.assignment.map-role', $res->id) }}" method="POST">
                                                @csrf
                                                <select name="role_profile_id" class="form-select form-select-sm" onchange="this.form.submit()" style="font-size: 0.8rem; max-width: 200px;">
                                                    <option value="">-- Unmapped --</option>
                                                    @foreach($roleProfiles as $rp)
                                                        <option value="{{ $rp->id }}" {{ $res->role_profile_id == $rp->id ? 'selected' : '' }}>
                                                            {{ $rp->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            @php
                                                $score = $res->currentVersion?->ats_score ?? 85;
                                                $badgeColor = $score >= 90 ? 'bg-success-subtle text-success border-success-subtle' : 'bg-warning-subtle text-warning border-warning-subtle';
                                            @endphp
                                            <span class="badge border rounded-pill {{ $badgeColor }} fs-6">
                                                {{ $score }}%
                                            </span>
                                        </td>
                                        <td>
                                            @if($res->is_global_default)
                                                <span class="badge bg-primary px-2 py-1 rounded-pill">
                                                    <i class="bi bi-star-fill me-1"></i>Global Default
                                                </span>
                                            @elseif($res->role_profile_id)
                                                <span class="badge bg-secondary-subtle text-dark border border-secondary-subtle rounded-pill">
                                                    Role Linked
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted border rounded-pill">
                                                    Custom / Standalone
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end align-items-center gap-1">
                                                <a href="{{ route('resume.editor.index', $res->id) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">
                                                    <i class="bi bi-pencil-square me-1"></i>Edit in Studio
                                                </a>
                                                @if(!$res->is_global_default)
                                                    <form action="{{ route('resume.assignment.set-default', $res->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem;">
                                                            Set Default
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No resumes configured yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mode 4 AI Recommendation Matcher Tester -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 border-top border-primary border-4 mb-4" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-cpu-fill text-primary fs-4"></i>
                        <h6 class="fw-bold text-dark mb-0">Mode 4: AI Match Resolver</h6>
                    </div>
                    <p class="text-secondary small mb-3">
                        Test how the CV resolver pipeline evaluates a vacancy to recommend the highest scoring resume.
                    </p>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Target Job Title</label>
                        <input type="text" id="testJobTitle" class="form-control form-control-sm" placeholder="e.g. Senior Backend Engineer" value="Senior Laravel Developer">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Required Skills (Comma-separated)</label>
                        <input type="text" id="testSkills" class="form-control form-control-sm" placeholder="PHP, Laravel, MySQL, Redis" value="PHP 8.4, Laravel, TiDB Cloud, Redis">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Job Description Snippet</label>
                        <textarea id="testDesc" rows="3" class="form-control form-control-sm" placeholder="Paste vacancy requirements...">Architecting scalable microservices and database clustering.</textarea>
                    </div>

                    <button type="button" id="evaluateBtn" class="btn btn-sm btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-bullseye"></i>
                        <span>Evaluate Best Matching CV</span>
                    </button>

                    <!-- Match Result Box -->
                    <div id="matchResultBox" class="mt-3 p-3 bg-light rounded border d-none">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark" id="resResume"></span>
                            <span class="badge bg-success text-white" id="resScore"></span>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2" id="resMode"></span>
                        <p class="small text-secondary mb-0" id="resReasoning"></p>
                    </div>
                </div>
            </div>

            <!-- Pipeline Overview Explainer -->
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-2">Resolution Order Pipeline</h6>
                    <ol class="small text-secondary ps-3 mb-0" style="line-height: 1.6;">
                        <li><strong>Mode 3 (Override)</strong>: Explicit application resume attachment.</li>
                        <li><strong>Mode 2 (Role-Linked)</strong>: Resume bound to target role profile.</li>
                        <li><strong>Mode 4 (AI Match)</strong>: Semantic keyword match against vacancy.</li>
                        <li><strong>Mode 1 (Global Default)</strong>: Primary fallback master resume.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const evaluateBtn = document.getElementById('evaluateBtn');
    const matchResultBox = document.getElementById('matchResultBox');
    const resResume = document.getElementById('resResume');
    const resScore = document.getElementById('resScore');
    const resMode = document.getElementById('resMode');
    const resReasoning = document.getElementById('resReasoning');

    evaluateBtn.addEventListener('click', function() {
        const title = document.getElementById('testJobTitle').value.trim();
        const skills = document.getElementById('testSkills').value.trim();
        const desc = document.getElementById('testDesc').value.trim();

        if (!title) return;

        fetch("{{ route('resume.assignment.test-match') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                job_title: title,
                skills: skills,
                description: desc
            })
        })
        .then(res => res.json())
        .then(data => {
            resResume.textContent = data.recommended_resume;
            resScore.textContent = data.match_score + '% Match';
            resMode.textContent = data.mode;
            resReasoning.textContent = data.reasoning;
            matchResultBox.classList.remove('d-none');
        })
        .catch(err => {
            alert('Evaluation failed: ' + err.message);
        });
    });
});
</script>
@endpush
@endsection
