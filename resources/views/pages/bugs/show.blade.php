@extends('layouts.app')

@section('title', 'Detail Bug [' . $bug->bug_id . '] — QA-TPL-001')

@section('content')
<div class="content-wrapper" style="max-width: 1000px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <a href="{{ route('bugs.index') }}" style="color: #64748b; text-decoration: none; font-size: 13px;">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Bug List
                </a>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span style="font-family: monospace; font-size: 18px; font-weight: 800; background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 8px; border: 1px solid #bfdbfe;">
                    {{ $bug->bug_id }}
                </span>
                <h1 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0;">{{ $bug->title }}</h1>
            </div>
            <p style="color: #64748b; font-size: 13px; margin: 6px 0 0;">
                Project: <strong>{{ $bug->project->name ?? 'General' }}</strong> &bull; Ditemukan: {{ $bug->found_date ? $bug->found_date->format('d M Y') : '-' }} &bull; Oleh: {{ $bug->reported_by }}
            </p>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="{{ route('bugs.edit', $bug->id) }}" class="btn-gradient-primary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-size: 13px; padding: 8px 16px;">
                <i class="fas fa-edit"></i> Edit / Retest
            </a>
        </div>
    </div>

    <!-- Quick Status Badges Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 24px;">
        <div style="background: white; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status Defect</div>
            <div style="margin-top: 4px;">
                @php
                    $stStyle = match($bug->status) {
                        'Open'            => 'background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;',
                        'In Progress'     => 'background: #fef9c3; color: #a16207; border: 1px solid #fde047;',
                        'Fixed'           => 'background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe;',
                        'Ready to Retest' => 'background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4;',
                        'Closed'          => 'background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;',
                        'Reopen'          => 'background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;',
                        'Rejected'        => 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;',
                        'Deferred'        => 'background: #e2e8f0; color: #334155; border: 1px solid #94a3b8;',
                        default           => 'background: #f1f5f9; color: #475569;',
                    };
                @endphp
                <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; display: inline-block; {!! $stStyle !!}">
                    {{ $bug->status }}
                </span>
            </div>
        </div>

        <div style="background: white; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Severity / Priority</div>
            <div style="font-size: 13.5px; font-weight: 700; color: #1e293b; margin-top: 4px;">
                {{ $bug->severity }} / <span style="color: #2563eb;">{{ $bug->priority }}</span>
            </div>
        </div>

        <div style="background: white; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Aging Defect</div>
            <div style="font-size: 16px; font-weight: 800; color: {{ $bug->aging_days > 14 && $bug->is_active ? '#dc2626' : '#1e293b' }}; margin-top: 2px;">
                {{ $bug->aging_days }} Hari
            </div>
        </div>

        <div style="background: white; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Related Test Case ID</div>
            <div style="font-size: 13.5px; font-weight: 800; font-family: monospace; color: #4f46e5; margin-top: 4px;">
                {{ $bug->related_test_case_id ?: '— (Belum ditautkan)' }}
            </div>
        </div>
    </div>

    <!-- Main Details Card -->
    <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
            <i class="fas fa-bug text-rose-500 mr-2"></i> Detail Informasi Masalah
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px; font-size: 13px;">
            <div>
                <span style="color: #64748b; display: block; font-size: 11.5px;">Modul / Fitur:</span>
                <strong>{{ $bug->module_feature ?: '—' }}</strong>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 11.5px;">Tipe Defect:</span>
                <strong>{{ $bug->bug_type }}</strong>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 11.5px;">Environment:</span>
                <strong>{{ $bug->environment }}</strong>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 11.5px;">Device / Browser / Unit ID:</span>
                <strong>{{ $bug->device_environment ?: '—' }}</strong>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 11.5px;">Versi Build Ditemukan:</span>
                <strong>{{ $bug->build_version_found ?: '—' }}</strong>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 11.5px;">Assigned Developer:</span>
                <strong>{{ $bug->assigned_to_dev ?: '— (Belum ditugaskan)' }}</strong>
            </div>
        </div>

        <div style="margin-bottom: 18px;">
            <div style="font-weight: 700; color: #334155; font-size: 13px; margin-bottom: 6px;">Langkah Reproduksi (Steps to Reproduce):</div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; font-size: 13px; white-space: pre-line; line-height: 1.6; color: #1e293b;">
                {{ $bug->steps_to_reproduce ?: 'Tidak ada langkah reproduksi tertulis.' }}
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 18px;">
            <div>
                <div style="font-weight: 700; color: #15803d; font-size: 13px; margin-bottom: 6px;">Expected Result:</div>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; font-size: 13px; color: #14532d; min-height: 60px;">
                    {{ $bug->expected_result ?: '—' }}
                </div>
            </div>
            <div>
                <div style="font-weight: 700; color: #b91c1c; font-size: 13px; margin-bottom: 6px;">Actual Result:</div>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px; font-size: 13px; color: #7f1d1d; min-height: 60px;">
                    {{ $bug->actual_result ?: '—' }}
                </div>
            </div>
        </div>

        @if($bug->evidence_url)
            <div>
                <div style="font-weight: 700; color: #334155; font-size: 13px; margin-bottom: 6px;">Evidence Bukti:</div>
                <a href="{{ $bug->evidence_url }}" target="_blank" rel="noopener noreferrer" style="color: #2563eb; font-weight: 600; font-size: 13px; word-break: break-all; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-external-link-alt"></i> {{ $bug->evidence_url }}
                </a>
            </div>
        @endif
    </div>

    <!-- Developer & QA Retest History -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 40px;">
        <!-- Dev Fix Section -->
        <div style="background: white; border-radius: 16px; padding: 20px; border: 1px solid #fed7aa;">
            <h4 style="font-size: 14px; font-weight: 700; color: #c2410c; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-code"></i> Perbaikan Developer
            </h4>
            <div style="font-size: 12.5px; margin-bottom: 8px;">
                <span style="color: #64748b;">Tanggal Fix:</span> <strong>{{ $bug->fixed_date ? $bug->fixed_date->format('d M Y') : '—' }}</strong>
            </div>
            <div style="font-size: 12.5px; margin-bottom: 10px;">
                <span style="color: #64748b;">Versi Fix:</span> <strong>{{ $bug->build_version_fix ?: '—' }}</strong>
            </div>
            <div style="font-size: 12.5px; color: #64748b; margin-bottom: 4px;">Catatan Dev:</div>
            <div style="background: #fff7ed; padding: 10px; border-radius: 8px; font-size: 12.5px; color: #7c2d12; border: 1px solid #ffedd5; min-height: 50px;">
                {{ $bug->dev_notes ?: 'Belum ada catatan perbaikan.' }}
            </div>
        </div>

        <!-- QA Retest Section -->
        <div style="background: white; border-radius: 16px; padding: 20px; border: 1px solid #bbf7d0;">
            <h4 style="font-size: 14px; font-weight: 700; color: #15803d; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-check-double"></i> Retest QA
            </h4>
            <div style="font-size: 12.5px; margin-bottom: 8px;">
                <span style="color: #64748b;">Tanggal Retest:</span> <strong>{{ $bug->retest_date ? $bug->retest_date->format('d M Y') : '—' }}</strong>
            </div>
            <div style="font-size: 12.5px; margin-bottom: 10px;">
                <span style="color: #64748b;">Retest Oleh:</span> <strong>{{ $bug->retested_by ?: '—' }}</strong>
            </div>
            <div style="font-size: 12.5px; color: #64748b; margin-bottom: 4px;">Hasil Retest QA:</div>
            <div style="background: #f0fdf4; padding: 10px; border-radius: 8px; font-size: 12.5px; color: #14532d; border: 1px solid #dcfce7; min-height: 50px;">
                {{ $bug->qa_retest_notes ?: 'Belum ada verifikasi retest.' }}
            </div>
        </div>
    </div>
</div>
@endsection
