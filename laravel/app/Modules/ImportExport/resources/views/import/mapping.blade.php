@extends('layouts.app')

@section('title', 'Confirm Column Schema Mapping')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-diagram-3-fill text-primary me-2"></i>Review & Confirm Column Mapping
            </h3>
            <p class="text-secondary small mb-0">
                File: <strong>{{ $filename }}</strong> ({{ $fileType }}) &bull; Verify that each incoming column matches its JobPilot AI destination.
            </p>
        </div>
        <a href="{{ route('import.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel Import
        </a>
    </div>

    @if(!empty($missingRequired))
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>
                <strong>Notice:</strong> The following mandatory columns must be mapped before proceeding:
                <strong>{{ implode(', ', $missingRequired) }}</strong>.
            </div>
        </div>
    @endif

    <form action="{{ route('import.confirm') }}" method="POST">
        @csrf
        <div class="row g-4">
            <!-- Left: Mapping Form -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0" style="border-radius: 14px;">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <span class="fw-bold text-dark">Detected Columns ({{ count($headers) }})</span>
                    </div>
                    <div class="card-body p-4">
                        @foreach($headers as $header)
                            <div class="mb-3 p-3 rounded border bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark font-monospace small">{{ $header }}</span>
                                    <span class="small text-muted">Uploaded Header</span>
                                </div>
                                <select name="mapping[{{ $header }}]" class="form-select form-select-sm">
                                    <option value="ignore">-- Ignore this column --</option>
                                    @foreach($canonicalFields as $key => $info)
                                        <option value="{{ $key }}" {{ ($inferredMapping[$header] ?? '') === $key ? 'selected' : '' }}>
                                            {{ $info['label'] }} {{ $info['required'] ? '(Required)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-check2-circle me-1"></i>Execute Ingestion & Deduplication
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Sample Data Preview -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0" style="border-radius: 14px;">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <span class="fw-bold text-dark">Top Sample Rows Preview</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        @foreach($headers as $h)
                                            <th>{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sampleRows as $row)
                                        <tr>
                                            @foreach($headers as $h)
                                                <td>{{ Str::limit($row[$h] ?? '', 30) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

