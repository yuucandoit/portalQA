@extends('layouts.app')

@section('title', 'Bug List & Defect Tracking — QA-TPL-001')

@section('content')
<div class="content-wrapper">
    <!-- Header Page -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1 style="font-size: 24px; font-weight: 800; color: #1e293b; margin: 0;">Bug List & Defect Tracking</h1>
                <span style="background: #fee2e2; color: #dc2626; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px; border: 1px solid #fecaca;">
                    QA-TPL-001 v0.2
                </span>
            </div>
            <p style="color: #64748b; font-size: 13.5px; margin: 4px 0 0;">
                Pencatatan defect software & hardware. Setiap status <strong>Fail</strong> di test case wajib terhubung ke Bug ID di sini.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('bugs.create') }}" class="btn-gradient-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                <i class="fas fa-plus"></i> Catat Bug Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle text-green-600"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- KPI Summary Cards (QA-TPL-001 Summary_Bug) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="kpi-card" style="background: white; border-radius: 14px; padding: 18px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Total Bug</div>
            <div style="font-size: 28px; font-weight: 800; color: #1e293b; margin-top: 4px;">{{ $kpis['total'] }}</div>
            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Tercatat dalam sistem</div>
        </div>

        <div class="kpi-card" style="background: white; border-radius: 14px; padding: 18px; border: 1px solid #fed7aa; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size: 12px; font-weight: 700; color: #ea580c; text-transform: uppercase; letter-spacing: 0.5px;">Bug Aktif</div>
            <div style="font-size: 28px; font-weight: 800; color: #c2410c; margin-top: 4px;">{{ $kpis['active'] }}</div>
            <div style="font-size: 11.5px; color: #9a3412; margin-top: 2px;">Open / Progress / Reopen / Ready</div>
        </div>

        <div class="kpi-card" style="background: white; border-radius: 14px; padding: 18px; border: 1px solid {{ $kpis['critical_major'] > 0 ? '#fecaca' : '#e2e8f0' }}; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size: 12px; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px;">Critical + Major Aktif</div>
            <div style="font-size: 28px; font-weight: 800; color: {{ $kpis['critical_major'] > 0 ? '#b91c1c' : '#1e293b' }}; margin-top: 4px;">{{ $kpis['critical_major'] }}</div>
            <div style="font-size: 11.5px; color: #991b1b; margin-top: 2px;">Memerlukan atensi segera</div>
        </div>

        <div class="kpi-card" style="background: white; border-radius: 14px; padding: 18px; border: 1px solid #bbf7d0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size: 12px; font-weight: 700; color: #16a34a; text-transform: uppercase; letter-spacing: 0.5px;">Bug Closed</div>
            <div style="font-size: 28px; font-weight: 800; color: #15803d; margin-top: 4px;">{{ $kpis['closed'] }}</div>
            <div style="font-size: 11.5px; color: #166534; margin-top: 2px;">Lolos retest QA</div>
        </div>

        <div class="kpi-card" style="background: white; border-radius: 14px; padding: 18px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Reopen Rate</div>
            <div style="font-size: 28px; font-weight: 800; color: {{ $kpis['reopen_rate'] > 15 ? '#dc2626' : '#2563eb' }}; margin-top: 4px;">{{ $kpis['reopen_rate'] }}%</div>
            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Indikator kualitas fix dev</div>
        </div>
    </div>

    <!-- Collapsible Summary_Bug Matrix (QA-TPL-001) -->
    <details style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 24px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <summary style="padding: 14px 20px; font-weight: 700; color: #1e293b; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; font-size: 14px; user-select: none;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-table text-indigo-500"></i>
                <span>Matriks Severity × Status Bug (Summary_Bug QA-TPL-001)</span>
            </div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600;">Klik untuk buka/tutup</span>
        </summary>
        <div style="padding: 20px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: center;">
                <thead>
                    <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                        <th style="padding: 10px 14px; text-align: left; font-weight: 700; color: #334155;">Severity \ Status</th>
                        @foreach($statuses as $st)
                            <th style="padding: 10px 12px; font-weight: 700; color: #334155;">{{ $st }}</th>
                        @endforeach
                        <th style="padding: 10px 14px; font-weight: 800; color: #1e293b; background: #e2e8f0;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($severities as $sev)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 14px; text-align: left; font-weight: 700;">
                                @if($sev === 'Critical')
                                    <span style="color: #dc2626;"><i class="fas fa-radiation mr-1"></i> Critical</span>
                                @elseif($sev === 'Major')
                                    <span style="color: #ea580c;"><i class="fas fa-exclamation-triangle mr-1"></i> Major</span>
                                @elseif($sev === 'Minor')
                                    <span style="color: #0284c7;"><i class="fas fa-info-circle mr-1"></i> Minor</span>
                                @else
                                    <span style="color: #64748b;"><i class="fas fa-circle mr-1"></i> Trivial</span>
                                @endif
                            </td>
                            @foreach($statuses as $st)
                                @php $cnt = $matrix[$sev][$st] ?? 0; @endphp
                                <td style="padding: 10px 12px; font-weight: {{ $cnt > 0 ? '700' : '400' }}; color: {{ $cnt > 0 ? '#1e293b' : '#cbd5e1' }}; {{ ($st === 'Open' || $st === 'Reopen') && in_array($sev, ['Critical', 'Major']) && $cnt > 0 ? 'background: #fef2f2; color: #b91c1c;' : '' }}">
                                    {{ $cnt }}
                                </td>
                            @endforeach
                            <td style="padding: 10px 14px; font-weight: 800; background: #f8fafc; color: #1e293b;">
                                {{ $matrix[$sev]['total'] }}
                            </td>
                        </tr>
                    @endforeach
                    <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1;">
                        <td style="padding: 12px 14px; text-align: left; color: #1e293b;">Total per Status</td>
                        @foreach($statuses as $st)
                            <td style="padding: 12px 12px; color: #1e293b;">{{ $statusTotals[$st] }}</td>
                        @endforeach
                        <td style="padding: 12px 14px; background: #e2e8f0; color: #0f172a; font-size: 13.5px;">{{ $kpis['total'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </details>

    <!-- Filter & Search Bar -->
    <div style="background: white; border-radius: 14px; padding: 18px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <form method="GET" action="{{ route('bugs.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 220px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Cari Bug ID, Judul, atau TC ID</label>
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: #94a3b8;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: BUG-001, login, SPC-LOGIN-002..."
                           style="width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none;">
                </div>
            </div>

            <div style="min-width: 170px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Project</label>
                <select name="project_id" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: white;">
                    <option value="">Semua Project</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 140px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Status</label>
                <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: white;">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 140px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Severity</label>
                <select name="severity" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: white;">
                    <option value="">Semua Severity</option>
                    @foreach($severities as $sev)
                        <option value="{{ $sev }}" {{ request('severity') == $sev ? 'selected' : '' }}>{{ $sev }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'project_id', 'status', 'severity', 'priority']))
                    <a href="{{ route('bugs.index') }}" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none;">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bugs Table (Aligned with QA-TPL-001 Bug List) -->
    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 14px 18px;">Bug ID</th>
                        <th style="padding: 14px 14px;">Tanggal</th>
                        <th style="padding: 14px 14px;">Project & Modul</th>
                        <th style="padding: 14px 18px;">Judul Bug</th>
                        <th style="padding: 14px 14px;">Severity / Priority</th>
                        <th style="padding: 14px 14px;">Status</th>
                        <th style="padding: 14px 14px;">Related TC ID</th>
                        <th style="padding: 14px 14px;">PIC Dev / QA</th>
                        <th style="padding: 14px 14px;">Aging</th>
                        <th style="padding: 14px 18px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bugs as $bug)
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                            <!-- Bug ID -->
                            <td style="padding: 14px 18px; white-space: nowrap;">
                                <span style="background: #eff6ff; color: #1d4ed8; font-weight: 800; font-family: monospace; font-size: 12px; padding: 4px 8px; border-radius: 6px; border: 1px solid #bfdbfe;">
                                    {{ $bug->bug_id }}
                                </span>
                            </td>

                            <!-- Tanggal -->
                            <td style="padding: 14px 14px; white-space: nowrap; color: #64748b; font-size: 12.5px;">
                                {{ $bug->found_date ? $bug->found_date->format('d/m/Y') : '-' }}
                            </td>

                            <!-- Project & Modul -->
                            <td style="padding: 14px 14px;">
                                <div style="font-weight: 700; color: #1e293b;">{{ $bug->project->name ?? 'General' }}</div>
                                <div style="font-size: 11.5px; color: #64748b;">{{ $bug->module_feature ?? '-' }}</div>
                            </td>

                            <!-- Judul & Tipe -->
                            <td style="padding: 14px 18px;">
                                <a href="{{ route('bugs.show', $bug->id) }}" style="font-weight: 600; color: #0f172a; text-decoration: none; display: block; margin-bottom: 2px;">
                                    {{ Str::limit($bug->title, 45) }}
                                </a>
                                <span style="background: #f1f5f9; color: #475569; font-size: 11px; padding: 2px 6px; border-radius: 4px;">
                                    {{ $bug->bug_type }}
                                </span>
                            </td>

                            <!-- Severity & Priority -->
                            <td style="padding: 14px 14px; white-space: nowrap;">
                                <div>
                                    @if($bug->severity === 'Critical')
                                        <span style="background: #fee2e2; color: #dc2626; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">Critical</span>
                                    @elseif($bug->severity === 'Major')
                                        <span style="background: #ffedd5; color: #c2410c; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">Major</span>
                                    @elseif($bug->severity === 'Minor')
                                        <span style="background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">Minor</span>
                                    @else
                                        <span style="background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 4px;">Trivial</span>
                                    @endif
                                </div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                    {{ $bug->priority }}
                                </div>
                            </td>

                            <!-- Status Lifecycle -->
                            <td style="padding: 14px 14px; white-space: nowrap;">
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
                                        default           => 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;',
                                    };
                                @endphp
                                <span style="display: inline-block; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px; {!! $stStyle !!}">
                                    {{ $bug->status }}
                                </span>
                            </td>

                            <!-- Related TC ID (Two-Way Tracing) -->
                            <td style="padding: 14px 14px; white-space: nowrap;">
                                @if($bug->related_test_case_id)
                                    <span style="font-family: monospace; font-size: 11.5px; font-weight: 700; color: #4338ca; background: #e0e7ff; padding: 3px 6px; border-radius: 4px;">
                                        {{ $bug->related_test_case_id }}
                                    </span>
                                @else
                                    <span style="color: #cbd5e1; font-size: 12px;">—</span>
                                @endif
                            </td>

                            <!-- PIC Dev & QA -->
                            <td style="padding: 14px 14px; font-size: 12px; color: #475569;">
                                <div><i class="fas fa-code text-indigo-400 mr-1"></i> Dev: <strong>{{ $bug->assigned_to_dev ?: '—' }}</strong></div>
                                <div style="font-size: 11px; color: #64748b;"><i class="fas fa-user-check text-green-500 mr-1"></i> QA: {{ $bug->reported_by }}</div>
                            </td>

                            <!-- Aging Days -->
                            <td style="padding: 14px 14px; white-space: nowrap;">
                                <span style="font-size: 11.5px; font-weight: 700; color: {{ $bug->aging_days > 14 && $bug->is_active ? '#dc2626' : '#64748b' }};">
                                    {{ $bug->aging_days }} hari
                                </span>
                            </td>

                            <!-- Actions -->
                            <td style="padding: 14px 18px; text-align: right; white-space: nowrap;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <a href="{{ route('bugs.show', $bug->id) }}" title="Lihat Detail"
                                       style="background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; padding: 6px 9px; border-radius: 6px; font-size: 12px; text-decoration: none;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('bugs.edit', $bug->id) }}" title="Edit / Update Retest"
                                       style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 9px; border-radius: 6px; font-size: 12px; text-decoration: none;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('bugs.destroy', $bug->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus bug ini?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 9px; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="padding: 48px; text-align: center; color: #94a3b8;">
                                <i class="fas fa-bug-slash" style="font-size: 36px; margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
                                <div style="font-weight: 700; color: #475569; font-size: 15px;">Belum Ada Bug Tercatat</div>
                                <p style="font-size: 13px; margin: 4px 0 16px;">Catat defect saat menemukan kegagalan pengujian software atau hardware.</p>
                                <a href="{{ route('bugs.create') }}" class="btn-gradient-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                                    <i class="fas fa-plus"></i> Catat Bug Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bugs->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                {{ $bugs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
