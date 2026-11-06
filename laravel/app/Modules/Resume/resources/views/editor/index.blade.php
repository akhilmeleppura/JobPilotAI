@extends('layouts.app')

@section('title', 'LaTeX CV Studio — ' . $resume->title)

@section('content')
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('resume.assignment.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Matrix
            </a>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="fw-bold text-dark mb-0">{{ $resume->title }}</h4>
                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill">
                        v{{ $activeVersion->version_number }}
                    </span>
                    @if($resume->is_global_default)
                        <span class="badge bg-primary rounded-pill">Global Default</span>
                    @endif
                </div>
                <small class="text-muted">
                    Target Persona: <strong>{{ $resume->roleProfile?->name ?? $resume->target_role_name }}</strong>
                    &bull; Template: <code>{{ $activeVersion->template_name }}</code>
                </small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- ATS Live Score Badge -->
            <div class="badge px-3 py-2 rounded-pill d-flex align-items-center gap-2 {{ $atsAnalysis['score'] >= 88 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}">
                <i class="bi bi-shield-check fs-6"></i>
                <span class="fw-bold" id="badgeAtsScore">{{ $atsAnalysis['score'] }}% ATS Score</span>
                <span class="badge bg-white text-dark rounded-pill" id="badgeAtsStatus">{{ $atsAnalysis['status'] }}</span>
            </div>

            <!-- Action Buttons -->
            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm" id="compileBtn">
                <i class="bi bi-play-circle-fill"></i>
                <span id="compileBtnText">Compile PDF</span>
            </button>

            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#newVersionModal">
                <i class="bi bi-bookmark-plus me-1"></i>New Version
            </button>

            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#templatesModal">
                <i class="bi bi-grid me-1"></i>Templates
            </button>

            <!-- Export Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item" href="{{ route('resume.editor.download-pdf', ['id' => $resume->id, 'version' => $activeVersion->version_number]) }}">
                            <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Download PDF (.pdf)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('resume.editor.download-tex', ['id' => $resume->id, 'version' => $activeVersion->version_number]) }}">
                            <i class="bi bi-file-earmark-code text-primary me-2"></i>Download LaTeX Source (.tex)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#overleafModal">
                            <i class="bi bi-clouds-fill text-success me-2"></i>1-Click Overleaf Sync Guide
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Main Studio Split View -->
    <div class="row g-3">
        <!-- Left: LaTeX Editor & Terminal -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; display: flex; flex-direction: column;">
                <!-- Editor Toolbar -->
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                    <div class="d-flex align-items-center gap-1">
                        <span class="small fw-bold text-secondary me-2"><i class="bi bi-code-slash me-1"></i>LaTeX Source</span>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="insertSnippet('\\textbf{}', 8)">Bold</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="insertSnippet('\\textit{}', 8)">Italic</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="insertSnippet('\\item ', 6)">Bullet</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="insertSnippet('\\section{}', 9)">Section</button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted" id="charCount">0 characters</span>
                        <button type="button" class="btn btn-xs btn-link text-decoration-none p-0 text-muted" onclick="copyLatexSource()" title="Copy all code">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>

                <!-- Code Editor Textarea -->
                <div class="card-body p-0 flex-grow-1" style="min-height: 520px;">
                    <textarea 
                        id="latexEditor" 
                        class="form-control border-0 font-monospace" 
                        style="height: 100%; min-height: 520px; resize: vertical; font-size: 0.88rem; line-height: 1.5; background-color: #0f172a; color: #f8fafc; border-radius: 0 0 8px 8px; padding: 1rem;"
                        spellcheck="false"
                    >{{ $activeVersion->latex_source }}</textarea>
                </div>

                <!-- Compiler Status & Output Drawer -->
                <div class="card-footer bg-light p-2 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="small fw-semibold text-secondary d-flex align-items-center gap-2">
                            <span class="spinner-grow spinner-grow-sm text-primary d-none" id="compileSpinner"></span>
                            <span id="compilerEngineText">Engine: Ready (Ctrl+S to compile)</span>
                        </div>
                        <a class="small text-decoration-none" data-bs-toggle="collapse" href="#compilerLogsCollapse" role="button">
                            Toggle Compiler Logs <i class="bi bi-chevron-down"></i>
                        </a>
                    </div>
                    <div class="collapse" id="compilerLogsCollapse">
                        <pre id="compilerLogs" class="bg-dark text-success p-2 rounded small mb-0 font-monospace" style="max-height: 160px; overflow-y: auto;">Compilation logs will appear here...</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Live PDF Preview & ATS Scorecard -->
        <div class="col-lg-6">
            <div class="d-flex flex-column gap-3 h-100">
                <!-- PDF Preview Card -->
                <div class="card shadow-sm border-0 flex-grow-1" style="border-radius: 12px; min-height: 480px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small fw-bold text-dark"><i class="bi bi-eye text-primary me-1"></i>Live PDF Preview</span>
                            <span class="badge bg-light text-muted border" id="previewTimestamp">Updated just now</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a id="openNewTabPdf" href="{{ route('resume.editor.preview-pdf', ['id' => $resume->id, 'v' => $activeVersion->version_number]) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Open in new window">
                                <i class="bi bi-box-arrow-up-right me-1"></i>New Tab
                            </a>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="refreshPdfFrame()" title="Refresh Frame">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0" style="height: 440px; background-color: #cbd5e1;">
                        <iframe 
                            id="pdfPreviewFrame" 
                            src="{{ route('resume.editor.preview-pdf', ['id' => $resume->id, 'v' => $activeVersion->version_number]) }}" 
                            style="width: 100%; height: 100%; border: none;"
                            title="Resume PDF Preview">
                        </iframe>
                    </div>
                </div>

                <!-- ATS Optimizer Card -->
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-dark"><i class="bi bi-cpu text-primary me-1"></i>ATS Scoring & Section Breakdown</span>
                        <span class="small text-muted">Overleaf & Modern ATS Compatible</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small fw-bold text-success mb-1"><i class="bi bi-check2-circle me-1"></i>Detected Sections</div>
                                    <div class="d-flex flex-wrap gap-1" id="atsSectionsFound">
                                        @foreach($atsAnalysis['sections_found'] as $sec)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $sec }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small fw-bold text-warning mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Missing Sections</div>
                                    <div class="d-flex flex-wrap gap-1" id="atsSectionsMissing">
                                        @forelse($atsAnalysis['sections_missing'] as $sec)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $sec }}</span>
                                        @empty
                                            <span class="small text-muted">All key sections present!</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recommendations & Strengths Accordion -->
                        <div class="accordion accordion-flush" id="atsFeedbackAccordion">
                            <div class="accordion-item border rounded mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed py-2 px-3 small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#atsStrengthsCollapse">
                                        <i class="bi bi-trophy text-success me-2"></i>ATS Strengths ({{ count($atsAnalysis['strengths']) }})
                                    </button>
                                </h2>
                                <div id="atsStrengthsCollapse" class="accordion-collapse collapse" data-bs-parent="#atsFeedbackAccordion">
                                    <div class="accordion-body py-2 px-3">
                                        <ul class="small text-secondary mb-0 ps-3" id="atsStrengthsList">
                                            @foreach($atsAnalysis['strengths'] as $str)
                                                <li>{{ $str }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border rounded">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed py-2 px-3 small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#atsRecsCollapse">
                                        <i class="bi bi-lightning-charge text-primary me-2"></i>Actionable Recommendations ({{ count($atsAnalysis['recommendations']) + count($atsAnalysis['red_flags']) }})
                                    </button>
                                </h2>
                                <div id="atsRecsCollapse" class="accordion-collapse collapse" data-bs-parent="#atsFeedbackAccordion">
                                    <div class="accordion-body py-2 px-3">
                                        <ul class="small text-secondary mb-0 ps-3" id="atsRecsList">
                                            @foreach($atsAnalysis['red_flags'] as $rf)
                                                <li class="text-danger fw-semibold">{{ $rf }}</li>
                                            @endforeach
                                            @foreach($atsAnalysis['recommendations'] as $rec)
                                                <li>{{ $rec }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Version History Timeline -->
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-dark"><i class="bi bi-clock-history me-1 text-primary"></i>Version History ({{ $resume->versions->count() }})</span>
                    </div>
                    <div class="card-body p-2" style="max-height: 140px; overflow-y: auto;">
                        <ul class="list-group list-group-flush small">
                            @foreach($resume->versions as $v)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 {{ $v->id === $activeVersion->id ? 'bg-primary-subtle text-primary fw-bold' : '' }}">
                                    <div>
                                        <span>v{{ $v->version_number }} &bull; {{ $v->template_name }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $v->ats_score }}% ATS</span>
                                        <span class="text-muted d-block" style="font-size: 0.72rem;">{{ $v->optimization_notes ?? 'Saved checkpoint' }}</span>
                                    </div>
                                    <div class="d-flex gap-1">
                                        @if($v->id !== $activeVersion->id)
                                            <a href="{{ route('resume.editor.index', ['id' => $resume->id, 'version' => $v->version_number]) }}" class="btn btn-xs btn-outline-primary py-0 px-1">
                                                Switch
                                            </a>
                                        @else
                                            <span class="badge bg-primary">Active</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Save New Version Checkpoint -->
<div class="modal fade" id="newVersionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('resume.editor.save-version', $resume->id) }}" class="modal-content">
            @csrf
            <textarea name="latex_source" id="versionModalLatex" class="d-none"></textarea>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-bookmark-plus text-primary me-2"></i>Create Version Checkpoint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-secondary mb-3">
                    Saving creates an immutable snapshot (Version #{{ $resume->versions->max('version_number') + 1 }}). You can switch between versions at any time.
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Optimization Summary / Notes</label>
                    <input type="text" name="optimization_notes" class="form-control" placeholder="e.g. Added TiDB Cloud metrics, trimmed older experience" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm" onclick="syncModalLatex()">Save Version</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Choose ATS Template -->
<div class="modal fade" id="templatesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-grid-fill text-primary me-2"></i>Select ATS-Optimized Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-secondary mb-3">
                    Applying a template will generate a new version checkpoint with your profile details automatically populated.
                </p>
                <div class="row g-3">
                    @foreach($templates as $tmpl)
                        <div class="col-md-6">
                            <div class="card h-100 border p-3 hover-shadow {{ $activeVersion->template_name === $tmpl['key'] ? 'border-primary bg-primary-subtle' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0 text-dark">{{ $tmpl['name'] }}</h6>
                                    <span class="badge bg-success-subtle text-success border">{{ $tmpl['ats_score'] }}% ATS</span>
                                </div>
                                <p class="small text-secondary mb-2" style="font-size: 0.8rem;">{{ $tmpl['description'] }}</p>
                                <div class="small text-muted mb-3" style="font-size: 0.75rem;">
                                    <strong>Target:</strong> {{ $tmpl['recommended_role'] }}
                                </div>
                                <form method="POST" action="{{ route('resume.editor.apply-template', $resume->id) }}" class="mt-auto">
                                    @csrf
                                    <input type="hidden" name="template_key" value="{{ $tmpl['key'] }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                        Apply {{ $tmpl['name'] }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Overleaf 1-Click Guide -->
<div class="modal fade" id="overleafModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-clouds-fill text-success me-2"></i>Overleaf Compatibility Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-secondary">
                    All 6 JobPilot AI templates strictly use standard TeX Live packages (`article`, `geometry`, `enumitem`, `titlesec`, `hyperref`) with zero proprietary extensions, ensuring 100% compilation on Overleaf.
                </p>
                <ol class="small text-secondary ps-3 mb-3">
                    <li>Click <strong>Export &rarr; Download LaTeX Source (.tex)</strong>.</li>
                    <li>Open <a href="https://www.overleaf.com/project" target="_blank">Overleaf.com</a> and click <strong>New Project &rarr; Upload Project</strong>.</li>
                    <li>Drop the downloaded <code>.tex</code> file directly into Overleaf to compile or share in real-time.</li>
                </ol>
                <div class="p-3 bg-light rounded small border">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    JobPilot AI compiles in an isolated sandbox with <code>-no-shell-escape</code> protecting your data.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Client-side script for compilation, preview refresh, and hotkeys -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('latexEditor');
    const compileBtn = document.getElementById('compileBtn');
    const compileBtnText = document.getElementById('compileBtnText');
    const compileSpinner = document.getElementById('compileSpinner');
    const compilerEngineText = document.getElementById('compilerEngineText');
    const compilerLogs = document.getElementById('compilerLogs');
    const pdfFrame = document.getElementById('pdfPreviewFrame');
    const charCount = document.getElementById('charCount');
    const badgeAtsScore = document.getElementById('badgeAtsScore');
    const badgeAtsStatus = document.getElementById('badgeAtsStatus');
    const previewTimestamp = document.getElementById('previewTimestamp');

    function updateCharCount() {
        charCount.textContent = editor.value.length + ' characters';
    }
    editor.addEventListener('input', updateCharCount);
    updateCharCount();

    // Hotkey: Ctrl+S / Cmd+S triggers compile
    window.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            triggerCompile();
        }
    });

    compileBtn.addEventListener('click', triggerCompile);

    function triggerCompile() {
        compileSpinner.classList.remove('d-none');
        compileBtnText.textContent = 'Compiling...';
        compileBtn.disabled = true;
        compilerEngineText.textContent = 'Executing secure TeX compiler sandbox...';

        fetch("{{ route('resume.editor.compile', $resume->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                latex_source: editor.value,
                version_id: {{ $activeVersion->id }}
            })
        })
        .then(res => res.json())
        .then(data => {
            compileSpinner.classList.add('d-none');
            compileBtnText.textContent = 'Compile PDF';
            compileBtn.disabled = false;

            if (data.success) {
                compilerEngineText.textContent = 'Compiled via ' + data.engine + ' (' + data.duration_ms + 'ms)';
                compilerLogs.textContent = data.logs;
                
                // Refresh preview iframe
                pdfFrame.src = data.pdf_url;
                previewTimestamp.textContent = 'Updated ' + new Date().toLocaleTimeString();

                // Update ATS Scorecard
                if (data.ats) {
                    badgeAtsScore.textContent = data.ats.score + '% ATS Score';
                    badgeAtsStatus.textContent = data.ats.status;
                }
            } else {
                compilerEngineText.textContent = 'Compilation syntax error';
                compilerLogs.textContent = data.logs;
                alert('LaTeX Error: Please inspect the compiler output log.');
            }
        })
        .catch(err => {
            compileSpinner.classList.add('d-none');
            compileBtnText.textContent = 'Compile PDF';
            compileBtn.disabled = false;
            compilerEngineText.textContent = 'Compilation failed';
            compilerLogs.textContent = err.message;
        });
    }

    window.refreshPdfFrame = function() {
        pdfFrame.src = "{{ route('resume.editor.preview-pdf', ['id' => $resume->id, 'v' => $activeVersion->version_number]) }}&t=" + new Date().getTime();
    };

    window.syncModalLatex = function() {
        document.getElementById('versionModalLatex').value = editor.value;
    };

    window.insertSnippet = function(snippet, cursorOffset) {
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const text = editor.value;
        editor.value = text.substring(0, start) + snippet + text.substring(end);
        editor.selectionStart = editor.selectionEnd = start + cursorOffset;
        editor.focus();
        updateCharCount();
    };

    window.copyLatexSource = function() {
        navigator.clipboard.writeText(editor.value).then(() => {
            alert('LaTeX source code copied to clipboard!');
        });
    };
});
</script>
@endsection

