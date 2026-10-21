@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - Test Reports')

@section('page_title', 'Test Reports')
@section('page_subtitle', 'Kelola laporan pengujian QA — 1 Report dapat memuat banyak Test Cases')

@section('content')
    <!-- Alerts -->
    @if (session('success'))
        <div style="margin-bottom: 16px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #16a34a; cursor: pointer; font-size: 18px;">&times;</button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
            <div class="stat-number">{{ $stats['total'] }}</div>
            <div class="stat-label">Total Reports</div>
            <div class="stat-change up"><i class="fas fa-database"></i> Semua laporan QA</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number">{{ $stats['pass'] }}</div>
            <div class="stat-label">Test Cases PASS</div>
            <div class="stat-change up"><i class="fas fa-arrow-up"></i> Status PASS</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number">{{ $stats['failed'] }}</div>
            <div class="stat-label">Test Cases FAILED</div>
            <div class="stat-change down"><i class="fas fa-arrow-down"></i> Perlu ditinjau</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-percentage"></i></div>
            <div class="stat-number">{{ $passRate }}%</div>
            <div class="stat-label">Pass Rate</div>
            <div class="stat-change {{ $passRate >= 70 ? 'up' : 'down' }}">
                <i class="fas fa-chart-line"></i> Rata-rata keberhasilan
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="content-card">
        <div class="card-header" style="padding: 20px 24px; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 200px;">
                <h3 style="font-size: 18px; font-weight: 700; margin: 0;">
                    <i class="fas fa-file-alt" style="color: #667eea; margin-right: 8px;"></i>Laporan Pengujian
                </h3>
                <span style="background: rgba(102,126,234,0.15); color: #667eea; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    {{ $reports->total() }} Reports
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <form method="GET" action="{{ route('reports.index') }}" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama report / project..."
                               style="padding: 8px 12px 8px 34px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; width: 210px; outline: none;" />
                    </div>
                    <select name="test_type" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; background: white; outline: none;">
                        <option value="">Semua Tipe</option>
                        @foreach([
                            'functional'  => 'Functional',
                            'integration' => 'Integration',
                            'regression'  => 'Regression',
                            'performance' => 'Performance',
                            'reliability' => 'Reliability',
                            'stress'      => 'Stress',
                            'negative'    => 'Negative',
                            'validation'  => 'Validation',
                            'hardware'    => 'Hardware'
                        ] as $val => $label)
                            <option value="{{ $val }}" {{ request('test_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" style="background: #1e293b; color: white; border: none; padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-search mr-1"></i> Filter
                    </button>
                    @if(request('search') || request('test_type'))
                        <a href="{{ route('reports.index') }}" style="color: #64748b; font-size: 13px; text-decoration: none; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 10px; background: #f8fafc;">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    @endif
                </form>

                <a href="{{ route('reports.export.all') }}" style="background: #10b981; color: white; padding: 9px 16px; border-radius: 10px; font-weight: 600; font-size: 13.5px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(16,185,129,0.25); white-space: nowrap;">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>

                <a href="{{ route('reports.create') }}" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 9px 18px; border-radius: 10px; font-weight: 600; font-size: 13.5px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(102,126,234,0.3); white-space: nowrap;">
                    <i class="fas fa-plus"></i> Create Report
                </a>
            </div>
        </div>

        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="padding-left: 24px; width: 32%;">Nama Report</th>
                            <th>Project</th>
                            <th>Tipe</th>
                            <th>Test Cases</th>
                            <th>Hasil</th>
                            <th>Tanggal</th>
                            <th style="text-align: right; padding-right: 24px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            @php
                                $totalCases   = $report->test_case_count;
                                $passCount    = $report->pass_count;
                                $failedCount  = $report->failed_count;
                                $blockedCount = $report->blocked_count;
                                $notRunCount  = $report->not_run_count;
                                $naCount      = $report->na_count;
                                $overallStatus = $report->overall_status;
                            @endphp
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        @if($report->sprint)
                                            <span style="background: #e0e7ff; color: #4338ca; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 6px; font-family: monospace;">
                                                <i class="fas fa-running mr-1" style="font-size: 10px;"></i>{{ $report->sprint }}
                                            </span>
                                        @endif
                                        @if($report->cycle && $report->cycle !== 'Siklus 1')
                                            <span style="background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 6px;">
                                                {{ $report->cycle }}
                                            </span>
                                        @endif
                                        <a href="{{ route('reports.show', $report->id) }}" style="font-weight: 700; color: #1e293b; font-size: 14px; text-decoration: none;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#1e293b'">
                                            {{ $report->report_name }}
                                        </a>
                                    </div>
                                    <div style="font-size: 12px; color: #94a3b8; margin-top: 3px;">
                                        Dibuat oleh {{ $report->createdBy->name ?? '—' }} &bull; ID #{{ $report->id }}
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        @if($report->project && $report->project->project_code)
                                            <span style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-family: monospace; font-size: 11px; padding: 1px 6px; border-radius: 4px;">
                                                {{ $report->project->project_code }}
                                            </span>
                                        @endif
                                        <span style="font-weight: 600; color: #334155; font-size: 13.5px;">{{ $report->project->name ?? '—' }}</span>
                                    </div>
                                    @if($report->project)
                                        <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px;">{{ $report->project->version }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $typeColors = [
                                            'functional'  => 'badge-info',
                                            'integration' => 'badge-info',
                                            'regression'  => 'badge-warning',
                                            'performance' => 'badge-danger',
                                            'reliability' => 'badge-success',
                                            'stress'      => 'badge-danger',
                                            'negative'    => 'badge-warning',
                                            'validation'  => 'badge-primary',
                                            'hardware'    => 'badge-info',
                                        ];
                                    @endphp
                                    <span class="badge-status {{ $typeColors[$report->test_type] ?? 'badge-secondary' }}">
                                        {{ $report->test_type_label }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 3px; min-width: 110px;">
                                        <div style="font-size: 13px; font-weight: 700; color: #1e293b;">{{ $totalCases }} Test Case{{ $totalCases !== 1 ? 's' : '' }}</div>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            @if($passCount > 0)
                                                <span style="background: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 6px;">✓ {{ $passCount }} Pass</span>
                                            @endif
                                            @if($failedCount > 0)
                                                <span style="background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 6px;">✗ {{ $failedCount }} Fail</span>
                                            @endif
                                            @if($blockedCount > 0)
                                                <span style="background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 6px;">⊘ {{ $blockedCount }} Block</span>
                                            @endif
                                            @if($notRunCount > 0)
                                                <span style="background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 600; padding: 1px 7px; border-radius: 6px;">⏳ {{ $notRunCount }} Not Run</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($overallStatus === 'Pass')
                                        <span class="badge-status badge-success">Pass</span>
                                    @elseif($overallStatus === 'Fail')
                                        <span class="badge-status badge-danger">Fail</span>
                                    @elseif($overallStatus === 'Blocked')
                                        <span class="badge-status badge-warning">Blocked</span>
                                    @elseif($overallStatus === 'Not Run')
                                        <span class="badge-status" style="background:#f1f5f9; color:#475569;">Not Run</span>
                                    @else
                                        <span class="badge-status badge-info">In Progress</span>
                                    @endif
                                </td>
                                <td style="color: #64748b; font-size: 13px; white-space: nowrap;">
                                    {{ $report->report_date ? $report->report_date->format('d M Y') : '—' }}
                                </td>
                                <td style="text-align: right; padding-right: 24px;">
                                        @php
                                            $driveModalData = [
                                                'projectCode' => $report->project->project_code ?? 'PROJ',
                                                'projectName' => $report->project->name ?? 'General',
                                                'sprint'      => $report->sprint ?? '',
                                                'cycle'       => $report->cycle ?? 'Siklus 1',
                                                'reportName'  => $report->report_name,
                                                'date'        => $report->report_date ? $report->report_date->format('Y-m-d') : date('Y-m-d'),
                                                'testCases'   => $report->testCases->map(fn($tc) => [
                                                    'testCaseId' => $tc->test_case_id,
                                                    'title'      => $tc->title,
                                                    'status'     => $tc->status,
                                                    'bugTicket'  => $tc->bug_ticket,
                                                ])->values(),
                                            ];
                                        @endphp
                                        <a href="{{ route('reports.show', $report->id) }}"
                                           title="Lihat Detail & Quality Gate (QA-TPL-001)"
                                           style="background: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; padding: 6px 11px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-eye text-blue-600"></i> Detail
                                        </a>
                                        <button type="button"
                                                data-drive-info="{{ json_encode($driveModalData) }}"
                                                onclick="openDriveModal(JSON.parse(this.dataset.driveInfo))"
                                                title="Lihat Struktur Google Drive & Format Bukti (QA-WI-002)"
                                                style="background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fab fa-google-drive text-blue-500"></i> Drive
                                        </button>
                                        <a href="{{ route('reports.export.single', $report->id) }}"
                                           title="Export Excel Report Ini"
                                           style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </a>
                                        <a href="{{ route('reports.edit', $report->id) }}"
                                           style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('reports.destroy', $report->id) }}" method="POST"
                                              onsubmit="return confirm('Hapus report \'{{ addslashes($report->report_name) }}\' beserta {{ $totalCases }} test case(s)?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 56px 24px; color: #94a3b8;">
                                    <i class="fas fa-file-alt" style="font-size: 40px; display: block; margin-bottom: 12px; opacity: 0.35;"></i>
                                    <div style="font-size: 15px; font-weight: 600; color: #64748b;">Belum ada test report</div>
                                    <div style="font-size: 13px; margin-top: 6px;">Klik <strong>"Create Report"</strong> untuk membuat laporan pengujian pertama.</div>
                                    <a href="{{ route('reports.create') }}"
                                       style="display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 13.5px; text-decoration: none;">
                                        <i class="fas fa-plus"></i> Create Report
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9;">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('pages.report.partials.drive-modal')
@endsection
