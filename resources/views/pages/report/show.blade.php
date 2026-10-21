@extends('layouts.app')

@section('title', $report->report_name . ' - Detail Report QA')
@section('page_title', $report->report_name)
@section('page_subtitle', 'Tahap 2: Kelola, review, dan eksekusi test case dalam satu lembar kerja')

@section('content')
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
    $aiReviewCount = $report->testCases->where('review_status', 'needs_review')->count();
@endphp

<div class="report-detail-container">

    <!-- 1. TOP NAVIGATION & UTILITY BAR -->
    <div class="detail-topbar">
        <a href="{{ route('reports.index') }}" class="qa-btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <span>Kembali ke Daftar Report</span>
        </a>

        <div class="detail-utility-actions">
            <button type="button"
                    class="qa-btn-utility"
                    data-drive-info="{{ json_encode($driveModalData) }}"
                    onclick="openDriveModal(JSON.parse(this.dataset.driveInfo))">
                <i class="fab fa-google-drive" style="color: #2563eb;" aria-hidden="true"></i>
                <span>Struktur Drive (QA-WI-002)</span>
            </button>
            <a href="{{ route('reports.export.single', $report->id) }}" class="qa-btn-utility emerald">
                <i class="fas fa-file-excel" aria-hidden="true"></i>
                <span>Export Excel (QA-TPL-001)</span>
            </a>
            <a href="{{ route('reports.edit', $report->id) }}" class="qa-btn-utility">
                <i class="fas fa-pen" style="color: #475569;" aria-hidden="true"></i>
                <span>Edit Info Report</span>
            </a>
        </div>
    </div>

    <!-- 2. UNIFIED REPORT SUMMARY & QUALITY GATE CARD -->
    <section class="qa-card summary-card" aria-label="Ringkasan Report dan Quality Gate">
        <!-- Top Identity Row -->
        <div class="summary-header">
            <div class="summary-identity">
                <div class="summary-title-row">
                    <h1 class="summary-report-title">{{ $report->report_name }}</h1>
                    <span class="qa-badge-soft blue">{{ $report->test_type_label }}</span>
                    <span id="headerTcTotalBadge" class="qa-badge-soft slate">
                        {{ $report->testCases->count() }} Test Cases
                    </span>
                </div>

                <div class="summary-meta-list">
                    <span class="meta-item">
                        <i class="fas fa-folder" style="color: #3b82f6;" aria-hidden="true"></i>
                        <strong>{{ $report->project->name ?? 'General' }}</strong>
                        <code>{{ $report->project->project_code ?? 'QA' }}</code>
                    </span>
                    <span class="meta-sep" aria-hidden="true">&bull;</span>
                    <span class="meta-item">
                        <i class="fas fa-running" style="color: #6366f1;" aria-hidden="true"></i>
                        Sprint: <strong>{{ $report->sprint ?: '-' }}</strong>
                    </span>
                    <span class="meta-sep" aria-hidden="true">&bull;</span>
                    <span class="meta-item">
                        <i class="fas fa-sync-alt" style="color: #10b981;" aria-hidden="true"></i>
                        Siklus: <strong>{{ $report->cycle ?? 'Siklus 1' }}</strong>
                    </span>
                    <span class="meta-sep" aria-hidden="true">&bull;</span>
                    <span class="meta-item">
                        <i class="far fa-calendar-alt" style="color: #f59e0b;" aria-hidden="true"></i>
                        Tanggal: <strong>{{ $report->report_date ? $report->report_date->format('d M Y') : '-' }}</strong>
                    </span>
                    <span class="meta-sep" aria-hidden="true">&bull;</span>
                    <span class="meta-item">
                        <i class="far fa-user" style="color: #64748b;" aria-hidden="true"></i>
                        QA: <strong>{{ $report->createdBy->name ?? 'QA Engineer' }}</strong>
                    </span>
                </div>

                @if($report->description)
                    <div class="summary-desc-box">
                        {{ $report->description }}
                    </div>
                @endif
            </div>

            <!-- Quality Gate Verdict Pill -->
            <div id="qgVerdictBadgeContainer" class="verdict-box">
                <span class="verdict-label">Quality Gate (QA-STD-001)</span>
                @if($qg['verdict'] === 'GO')
                    <span id="qgVerdictBadge" class="verdict-pill go">
                        <i class="fas fa-check-circle" aria-hidden="true"></i> GO (SIAP RILIS)
                    </span>
                @elseif($qg['verdict'] === 'CONDITIONAL')
                    <span id="qgVerdictBadge" class="verdict-pill conditional">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> CONDITIONAL
                    </span>
                @else
                    <span id="qgVerdictBadge" class="verdict-pill nogo">
                        <i class="fas fa-times-circle" aria-hidden="true"></i> NO GO (BELUM SIAP)
                    </span>
                @endif
            </div>
        </div>

        <!-- 4 Aligned KPI Metric Cards -->
        <div class="kpi-strip-grid">
            <!-- KPI 1: Pass Rate -->
            <div class="kpi-box">
                <div class="kpi-box-top">
                    <span class="kpi-label">Pass Rate</span>
                    <span class="kpi-target">Target &ge; 95%</span>
                </div>
                <div class="kpi-main-row">
                    <span id="qgPassRateVal" class="kpi-value {{ $qg['pass_rate'] >= 95 ? 'text-emerald' : ($qg['pass_rate'] >= 80 ? 'text-amber' : 'text-rose') }}">
                        {{ $qg['pass_rate'] }}%
                    </span>
                    <span class="kpi-subtext">
                        <strong id="qgPassCountVal">{{ $qg['pass_count'] }}</strong> / <span id="qgExecutedCountVal">{{ $qg['executed_count'] }}</span> dieksekusi
                    </span>
                </div>
                <div class="kpi-progress-track">
                    <div id="qgPassRateBar" class="kpi-progress-fill {{ $qg['pass_rate'] >= 95 ? 'fill-emerald' : ($qg['pass_rate'] >= 80 ? 'fill-amber' : 'fill-rose') }}"
                         style="width: {{ min(100, max(0, $qg['pass_rate'])) }}%;"></div>
                </div>
            </div>

            <!-- KPI 2: Progress Eksekusi -->
            <div class="kpi-box">
                <div class="kpi-box-top">
                    <span class="kpi-label">Progress Eksekusi</span>
                    <span class="kpi-target">Target 100%</span>
                </div>
                <div class="kpi-main-row">
                    <span id="qgProgressVal" class="kpi-value text-slate">
                        {{ $qg['progress'] }}%
                    </span>
                    <span class="kpi-subtext">
                        <strong id="qgNotRunCountVal">{{ $qg['not_run_count'] }}</strong> belum dieksekusi
                    </span>
                </div>
                <div class="kpi-progress-track">
                    <div id="qgProgressBar" class="kpi-progress-fill fill-blue"
                         style="width: {{ min(100, max(0, $qg['progress'])) }}%;"></div>
                </div>
            </div>

            <!-- KPI 3: Distribusi Hasil -->
            <div class="kpi-box">
                <div class="kpi-box-top">
                    <span class="kpi-label">Distribusi Status</span>
                    <span class="kpi-target">Total <strong id="qgTotalCasesVal">{{ $qg['total_cases'] }}</strong> TC</span>
                </div>
                <div class="kpi-dist-pills">
                    <div class="dist-chip pass" title="Pass">
                        <span class="dist-dot"></span>
                        <span>Pass:</span>
                        <strong id="qgPassDistVal">{{ $qg['pass_count'] }}</strong>
                    </div>
                    <div class="dist-chip fail" title="Fail">
                        <span class="dist-dot"></span>
                        <span>Fail:</span>
                        <strong id="qgFailDistVal">{{ $qg['fail_count'] }}</strong>
                    </div>
                    <div class="dist-chip blocked" title="Blocked">
                        <span class="dist-dot"></span>
                        <span>Blocked:</span>
                        <strong id="qgBlockedDistVal">{{ $qg['blocked_count'] }}</strong>
                    </div>
                </div>
            </div>

            <!-- KPI 4: Defect Tracing -->
            <div class="kpi-box">
                <div class="kpi-box-top">
                    <span class="kpi-label">Pelacakan Defect</span>
                    <span class="kpi-target">Standar QA-TPL-001</span>
                </div>
                <div class="kpi-main-row">
                    <span id="qgFailWithoutBugVal" class="kpi-value {{ $qg['fail_without_bug'] > 0 ? 'text-rose' : 'text-emerald' }}">
                        {{ $qg['fail_without_bug'] }}
                    </span>
                    <span class="kpi-subtext">Fail tanpa tiket Bug</span>
                </div>
                <div id="qgDefectHint" class="kpi-foot-note {{ $qg['fail_without_bug'] > 0 ? 'warn' : 'ok' }}">
                    {{ $qg['fail_without_bug'] > 0 ? 'Wajib tautkan Bug ID pada status Fail' : 'Semua status Fail sudah memiliki Bug ID' }}
                </div>
            </div>
        </div>
    </section>

    <!-- 3. MAIN WORKSPACE CARD: TEST CASES TABLE & QUICK ADD -->
    <section class="qa-card workspace-card" aria-label="Lembar Kerja Test Case">

        <!-- Workspace Header: Title & 3 Ordered Ways to Add Test Cases -->
        <div class="workspace-header">
            <div class="workspace-title-group">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h2 class="workspace-title">Daftar Test Cases</h2>
                    <span id="tableHeaderCountBadge" class="qa-count-pill">{{ $report->testCases->count() }}</span>
                </div>
                <p class="workspace-subtitle">
                    Klik pada judul skenario atau tombol detail untuk membuka panel samping (drawer). Status dan prioritas dapat diubah langsung di tabel.
                </p>
            </div>

            <!-- 3 Action Buttons (Ordered Fastest to Slowest) -->
            <div class="workspace-actions" role="group" aria-label="Tambah test case">
                <!-- 1. Generate dengan AI (Fastest) -->
                <button type="button" class="qa-btn-primary indigo" onclick="openAiGenerateModal()">
                    <i class="fas fa-magic" aria-hidden="true"></i>
                    <span>Generate dengan AI</span>
                    <span class="speed-tag">Tercepat</span>
                </button>

                <!-- 2. Template / Import CSV -->
                <button type="button" class="qa-btn-secondary" onclick="openImportTemplateModal()">
                    <i class="fas fa-file-import" style="color: #2563eb;" aria-hidden="true"></i>
                    <span>Template / CSV</span>
                </button>

                <!-- 3. Tambah Manual -->
                <button type="button" class="qa-btn-secondary" onclick="focusQuickAddOrOpenDrawer()">
                    <i class="fas fa-plus" style="color: #0f172a;" aria-hidden="true"></i>
                    <span>Tambah Manual</span>
                </button>
            </div>
        </div>

        <!-- Quick Inline Add Bar (Fast Manual Entry without leaving table) -->
        <div class="quick-add-bar">
            <div class="quick-add-input-wrap">
                <i class="fas fa-plus-circle quick-add-icon" aria-hidden="true"></i>
                <input type="text" id="quickAddTitleInput" class="quick-add-input"
                       placeholder="Tambah manual cepat: Ketik skenario pengujian baru di sini lalu tekan Enter..."
                       onkeydown="if(event.key === 'Enter'){ event.preventDefault(); submitQuickInlineAdd(false); }" />
            </div>
            <div class="quick-add-controls">
                <select id="quickAddPrioritySelect" class="quick-add-select" aria-label="Prioritas test case baru">
                    <option value="Critical">Critical</option>
                    <option value="High">High</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="Low">Low</option>
                </select>
                <button type="button" id="btnQuickAddSave" class="qa-btn-outline-blue sm" onclick="submitQuickInlineAdd(false)" title="Simpan ke tabel (Enter)">
                    Tambah
                </button>
                <button type="button" class="qa-btn-secondary sm" onclick="submitQuickInlineAdd(true)" title="Simpan lalu buka rincian langkah di panel samping">
                    + Rincian Langkah
                </button>
            </div>
        </div>

        <!-- Filter Tabs & Search Bar -->
        <div class="workspace-toolbar">
            <div class="filter-tabs" role="tablist" aria-label="Filter status test case">
                <button type="button" class="tc-filter-btn active" data-filter="all" onclick="filterTableByStatus('all', this)">
                    <span>Semua</span>
                    <span id="filterCountAll" class="filter-count">{{ $report->testCases->count() }}</span>
                </button>
                <button type="button" class="tc-filter-btn" data-filter="Not Executed" onclick="filterTableByStatus('Not Executed', this)">
                    <span class="status-dot not-run"></span>
                    <span>Not Executed</span>
                    <span id="filterCountNotExecuted" class="filter-count">{{ $qg['not_run_count'] }}</span>
                </button>
                <button type="button" class="tc-filter-btn" data-filter="Pass" onclick="filterTableByStatus('Pass', this)">
                    <span class="status-dot pass"></span>
                    <span>Pass</span>
                    <span id="filterCountPass" class="filter-count">{{ $qg['pass_count'] }}</span>
                </button>
                <button type="button" class="tc-filter-btn" data-filter="Fail" onclick="filterTableByStatus('Fail', this)">
                    <span class="status-dot fail"></span>
                    <span>Fail</span>
                    <span id="filterCountFail" class="filter-count">{{ $qg['fail_count'] }}</span>
                </button>
                <button type="button" class="tc-filter-btn" data-filter="Blocked" onclick="filterTableByStatus('Blocked', this)">
                    <span class="status-dot blocked"></span>
                    <span>Blocked</span>
                    <span id="filterCountBlocked" class="filter-count">{{ $qg['blocked_count'] }}</span>
                </button>
                <button type="button" class="tc-filter-btn ai-tab" data-filter="needs_review" onclick="filterTableByStatus('needs_review', this)">
                    <i class="fas fa-magic" style="color: #a21caf; font-size: 11px;" aria-hidden="true"></i>
                    <span>Perlu Review AI</span>
                    <span id="filterCountAiReview" class="filter-count ai">{{ $aiReviewCount }}</span>
                </button>
            </div>

            <div class="search-input-wrap">
                <i class="fas fa-search search-icon" aria-hidden="true"></i>
                <input type="text" id="tcSearchInput" class="search-input"
                       placeholder="Cari ID atau skenario..."
                       oninput="searchTableTestCases(this.value)" />
                <button type="button" id="btnClearSearch" class="search-clear-btn" style="display: none;"
                        onclick="document.getElementById('tcSearchInput').value=''; searchTableTestCases('');"
                        aria-label="Bersihkan pencarian">
                    &times;
                </button>
            </div>
        </div>

        <!-- Contextual Bulk Action Bar (Aksi Massal) -->
        <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
            <div class="bulk-left">
                <span id="bulkSelectedCountBadge" class="bulk-count-badge">0 dipilih</span>
                <span class="bulk-label">Ubah status sekaligus:</span>
                <div class="bulk-status-btns" role="group" aria-label="Ubah status massal">
                    <button type="button" class="bulk-st-btn pass" onclick="executeBulkStatusDirect('Pass')">
                        Pass
                    </button>
                    <button type="button" class="bulk-st-btn fail" onclick="executeBulkStatusDirect('Fail')">
                        Fail
                    </button>
                    <button type="button" class="bulk-st-btn blocked" onclick="executeBulkStatusDirect('Blocked')">
                        Blocked
                    </button>
                    <button type="button" class="bulk-st-btn not-run" onclick="executeBulkStatusDirect('Not Executed')">
                        Not Executed
                    </button>
                </div>
            </div>

            <div class="bulk-right">
                <button type="button" class="bulk-act-btn ai" onclick="executeBulkApproveAi()">
                    <i class="fas fa-check-double" aria-hidden="true"></i> Setujui Draft AI
                </button>
                <button type="button" class="bulk-act-btn" onclick="executeBulkDuplicate()">
                    <i class="fas fa-copy" aria-hidden="true"></i> Duplikat
                </button>
                <button type="button" class="bulk-act-btn danger" onclick="executeBulkDelete()">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Hapus
                </button>
                <button type="button" class="bulk-cancel-btn" onclick="clearBulkSelection()">
                    Batal
                </button>
            </div>
        </div>

        <!-- Test Cases Table -->
        <div class="tc-table-container">
            <table class="tc-table">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input type="checkbox" id="selectAllTcCheck" class="tc-checkbox" onchange="toggleSelectAllTc(this.checked)" aria-label="Pilih semua test case" />
                        </th>
                        <th style="width: 120px;">TC ID</th>
                        <th>Skenario Pengujian</th>
                        <th style="width: 130px;">Prioritas</th>
                        <th style="width: 156px;">Status Eksekusi</th>
                        <th style="width: 130px;">Bug / Defect</th>
                        <th style="width: 112px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="testCaseTableBody">
                    @forelse($report->testCases as $index => $tc)
                        @php
                            $dyn = is_array($tc->dynamic_fields) ? $tc->dynamic_fields : (json_decode($tc->dynamic_fields, true) ?? []);
                            $isAiDraft = ($tc->is_ai_generated && $tc->review_status === 'needs_review');
                            $normStatus = match($tc->status) {
                                'Pass', 'PASS' => 'Pass',
                                'Fail', 'FAIL', 'FAILED' => 'Fail',
                                'Blocked', 'BLOCKED' => 'Blocked',
                                'N/A', 'NA' => 'N/A',
                                default => 'Not Executed',
                            };
                            $normPrio = in_array($tc->priority, ['Critical', 'High', 'Medium', 'Low']) ? $tc->priority : 'Medium';
                        @endphp
                        <tr class="tc-row {{ $isAiDraft ? 'is-ai-draft' : '' }}"
                            id="tcRow-{{ $tc->id }}"
                            data-id="{{ $tc->id }}"
                            data-status="{{ $normStatus }}"
                            data-ai-review="{{ $isAiDraft ? 'needs_review' : 'reviewed' }}"
                            data-tc="{{ json_encode($tc->load('steps')) }}">

                            <!-- Checkbox -->
                            <td style="text-align: center;">
                                <input type="checkbox" class="tc-checkbox tc-row-check" value="{{ $tc->id }}" onchange="onTcRowCheckChange()" aria-label="Pilih {{ $tc->test_case_id }}" />
                            </td>

                            <!-- TC ID -->
                            <td>
                                <button type="button" class="tc-id-btn" onclick="openDrawerForId({{ $tc->id }})" title="Buka rincian test case">
                                    {{ $tc->test_case_id ?: 'TC-' . sprintf('%03d', $index + 1) }}
                                </button>
                            </td>

                            <!-- Scenario & Sub-meta -->
                            <td>
                                <div class="tc-scenario-wrap" onclick="openDrawerForId({{ $tc->id }})">
                                    <div class="tc-title-cell">{{ $tc->title }}</div>
                                    <div class="tc-submeta-row">
                                        @if($isAiDraft)
                                            <span class="ai-review-pill">
                                                <i class="fas fa-magic" aria-hidden="true"></i>
                                                <span>Dibuat AI &bull; Perlu Review</span>
                                                <button type="button" class="ai-inline-approve-btn"
                                                        onclick="event.stopPropagation(); approveSingleAiTc({{ $tc->id }})"
                                                        title="Setujui draft AI ini">
                                                    <i class="fas fa-check" aria-hidden="true"></i> Setujui
                                                </button>
                                            </span>
                                        @endif

                                        <span class="tc-meta-tag">
                                            {{ $dyn['caseType'] ?? 'Positive' }}
                                        </span>

                                        <span class="tc-step-count-tag">
                                            <i class="fas fa-list-ol" aria-hidden="true"></i>
                                            <span class="step-num-text">{{ $tc->steps->count() }} langkah</span>
                                        </span>

                                        @if(!empty($dyn['moduleFeature']))
                                            <span class="tc-module-tag">
                                                <i class="fas fa-cube" aria-hidden="true"></i> {{ $dyn['moduleFeature'] }}
                                            </span>
                                        @endif

                                        @if($tc->precondition)
                                            <span class="tc-precond-preview" title="Precondition: {{ $tc->precondition }}">
                                                Pre: {{ \Illuminate\Support\Str::limit($tc->precondition, 45) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Priority (Inline Editable Select) -->
                            <td>
                                <select class="tc-prio-select prio-{{ strtolower($normPrio) }}"
                                        onchange="onInlinePriorityChange({{ $tc->id }}, this.value, this)"
                                        aria-label="Prioritas {{ $tc->test_case_id }}">
                                    <option value="Critical" {{ $normPrio === 'Critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="High" {{ $normPrio === 'High' ? 'selected' : '' }}>High</option>
                                    <option value="Medium" {{ $normPrio === 'Medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="Low" {{ $normPrio === 'Low' ? 'selected' : '' }}>Low</option>
                                </select>
                            </td>

                            <!-- Status Eksekusi (Inline Editable Select) -->
                            <td>
                                <select class="tc-status-select st-{{ strtolower(str_replace([' ', '/'], '-', $normStatus)) }}"
                                        onchange="onInlineStatusChange({{ $tc->id }}, this.value, this)"
                                        aria-label="Status eksekusi {{ $tc->test_case_id }}">
                                    <option value="Not Executed" {{ $normStatus === 'Not Executed' ? 'selected' : '' }}>Not Executed</option>
                                    <option value="Pass" {{ $normStatus === 'Pass' ? 'selected' : '' }}>Pass</option>
                                    <option value="Fail" {{ $normStatus === 'Fail' ? 'selected' : '' }}>Fail</option>
                                    <option value="Blocked" {{ $normStatus === 'Blocked' ? 'selected' : '' }}>Blocked</option>
                                    <option value="N/A" {{ $normStatus === 'N/A' ? 'selected' : '' }}>N/A</option>
                                </select>
                            </td>

                            <!-- Bug Ticket -->
                            <td>
                                <div class="tc-bug-cell">
                                    @if($tc->bug_ticket)
                                        <span class="bug-ticket-pill" onclick="openDrawerForId({{ $tc->id }})" title="Klik untuk edit tiket bug">
                                            <i class="fas fa-bug" aria-hidden="true"></i> {{ $tc->bug_ticket }}
                                        </span>
                                    @elseif($normStatus === 'Fail')
                                        <button type="button" class="btn-log-bug-inline"
                                                onclick="openQuickBugForTcId({{ $tc->id }})">
                                            <i class="fas fa-plus" aria-hidden="true"></i> Catat Bug
                                        </button>
                                    @else
                                        <span class="empty-dash">-</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Row Actions -->
                            <td style="text-align: right;">
                                <div class="row-action-group">
                                    <button type="button" class="row-act-btn primary" onclick="openDrawerForId({{ $tc->id }})" title="Buka Detail & Langkah">
                                        <i class="fas fa-sliders-h" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="row-act-btn" onclick="duplicateSingleTc({{ $tc->id }})" title="Duplikat">
                                        <i class="fas fa-copy" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="row-act-btn danger" onclick="deleteSingleTc({{ $tc->id }})" title="Hapus">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyTestCasesRow">
                            <td colspan="7" class="empty-table-cell">
                                <div class="empty-state-box">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                                    </div>
                                    <h3 class="empty-state-title">Belum ada test case di report ini</h3>
                                    <p class="empty-state-desc">
                                        Mulai tambahkan test case menggunakan salah satu dari 3 cara berikut (urut dari yang paling cepat):
                                    </p>
                                    <div class="empty-state-actions">
                                        <button type="button" class="qa-btn-primary indigo" onclick="openAiGenerateModal()">
                                            <i class="fas fa-magic" aria-hidden="true"></i>
                                            <span>1. Generate dengan AI</span>
                                        </button>
                                        <button type="button" class="qa-btn-secondary" onclick="openImportTemplateModal()">
                                            <i class="fas fa-file-import" style="color: #2563eb;" aria-hidden="true"></i>
                                            <span>2. Dari Template / Import CSV</span>
                                        </button>
                                        <button type="button" class="qa-btn-secondary" onclick="document.getElementById('quickAddTitleInput').focus()">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                            <span>3. Ketik Manual</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- 4. LINKED BUGS CARD (TWO-WAY TRACING) -->
    @if(isset($bugs) && $bugs->isNotEmpty())
        <section class="qa-card bugs-card" aria-label="Daftar Defect Terkait">
            <div class="bugs-card-header">
                <div>
                    <h3 class="bugs-card-title">
                        <i class="fas fa-bug" style="color: #dc2626;" aria-hidden="true"></i>
                        <span>Defect / Bug Terkait ({{ $bugs->count() }})</span>
                    </h3>
                    <p class="bugs-card-subtitle">
                        Daftar tiket bug yang tertaut dengan skenario pada report ini.
                    </p>
                </div>
                <a href="{{ route('bugs.create') }}?project_id={{ $report->project_id }}&report_id={{ $report->id }}" class="qa-btn-secondary sm">
                    <i class="fas fa-plus" style="color: #dc2626;" aria-hidden="true"></i>
                    <span>Buka Halaman Bug Logger</span>
                </a>
            </div>
            <div class="tc-table-container">
                <table class="tc-table">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Bug ID</th>
                            <th>Judul Defect</th>
                            <th style="width: 130px;">Related TC ID</th>
                            <th style="width: 120px;">Severity</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 90px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bugs as $b)
                            <tr>
                                <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 700;">
                                    <a href="{{ route('bugs.show', $b->id) }}" style="color: #2563eb; text-decoration: none;">
                                        {{ $b->bug_id }}
                                    </a>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">{{ $b->title }}</td>
                                <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: #475569;">
                                    {{ $b->related_test_case_id ?: '-' }}
                                </td>
                                <td>
                                    <span class="qa-badge-soft {{ $b->severity === 'Critical' ? 'rose' : ($b->severity === 'Major' ? 'amber' : 'slate') }}">
                                        {{ $b->severity }}
                                    </span>
                                </td>
                                <td>
                                    <span class="qa-badge-soft blue">{{ $b->status }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('bugs.show', $b->id) }}" class="qa-btn-secondary sm" style="text-decoration: none;">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

</div>

<!-- Non-Blocking Toast Notification -->
<div id="qaToast" class="qa-toast" role="status" aria-live="polite" style="display: none;">
    <i id="qaToastIcon" class="fas fa-check-circle" aria-hidden="true"></i>
    <span id="qaToastMessage">Perubahan disimpan</span>
</div>

<!-- PARTIAL MODALS & SIDE DRAWER -->
@include('pages.report.partials.test-case-drawer')
@include('pages.report.partials.ai-generate-modal')
@include('pages.report.partials.import-template-modal')
@include('pages.report.partials.drive-modal')
@include('pages.bugs.partials.quick-bug-modal')

@endsection

@push('styles')
<style>
    .report-detail-container {
        max-width: 1380px;
        margin: 0 auto;
        padding-bottom: 64px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* 1. Topbar & Utility Buttons */
    .detail-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .qa-btn-back {
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s;
    }

    .qa-btn-back:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .detail-utility-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .qa-btn-utility {
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 8px 13px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: all 0.15s;
    }

    .qa-btn-utility:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .qa-btn-utility.emerald {
        background: #f0fdf4;
        color: #166534;
        border-color: #bbf7d0;
    }

    .qa-btn-utility.emerald:hover {
        background: #dcfce7;
        border-color: #86efac;
    }

    /* Cards Base */
    .qa-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    /* Soft Badges */
    .qa-badge-soft {
        font-size: 11.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .qa-badge-soft.blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .qa-badge-soft.green { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .qa-badge-soft.amber { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .qa-badge-soft.rose { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .qa-badge-soft.slate { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }

    /* 2. Unified Summary & Quality Gate Card */
    .summary-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    .summary-identity {
        flex: 1;
        min-width: 280px;
    }

    .summary-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .summary-report-title {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }

    .summary-meta-list {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 13px;
        color: #475569;
    }

    .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .meta-item code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11.5px;
        background: #f1f5f9;
        color: #1e293b;
        padding: 1px 5px;
        border-radius: 4px;
        font-weight: 600;
    }

    .meta-sep {
        color: #cbd5e1;
    }

    .summary-desc-box {
        margin-top: 10px;
        padding: 8px 12px;
        background: #f8fafc;
        border-left: 3px solid #2563eb;
        border-radius: 0 8px 8px 0;
        font-size: 13px;
        color: #334155;
        line-height: 1.5;
    }

    .verdict-box {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
    }

    .verdict-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .verdict-pill {
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .verdict-pill.go { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .verdict-pill.conditional { background: #fef3c7; color: #92400e; border: 1px solid #fde047; }
    .verdict-pill.nogo { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

    /* 4 KPI Strip Grid */
    .kpi-strip-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        background: #f8fafc;
    }

    .kpi-box {
        padding: 16px 20px;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 8px;
    }

    .kpi-box:last-child {
        border-right: none;
    }

    .kpi-box-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }

    .kpi-label {
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .kpi-target {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    .kpi-main-row {
        display: flex;
        align-items: baseline;
        gap: 8px;
    }

    .kpi-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .kpi-value.text-emerald { color: #15803d; }
    .kpi-value.text-amber { color: #b45309; }
    .kpi-value.text-rose { color: #dc2626; }
    .kpi-value.text-slate { color: #0f172a; }

    .kpi-subtext {
        font-size: 12px;
        color: #64748b;
    }

    .kpi-progress-track {
        height: 6px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }

    .kpi-progress-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.3s ease;
    }

    .kpi-progress-fill.fill-emerald { background: #16a34a; }
    .kpi-progress-fill.fill-amber { background: #d97706; }
    .kpi-progress-fill.fill-rose { background: #dc2626; }
    .kpi-progress-fill.fill-blue { background: #2563eb; }

    .kpi-dist-pills {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 2px;
    }

    .dist-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #334155;
    }

    .dist-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }
    .dist-chip.pass .dist-dot { background: #16a34a; }
    .dist-chip.fail .dist-dot { background: #dc2626; }
    .dist-chip.blocked .dist-dot { background: #d97706; }

    .kpi-foot-note {
        font-size: 11.5px;
        font-weight: 600;
    }
    .kpi-foot-note.warn { color: #b91c1c; }
    .kpi-foot-note.ok { color: #15803d; }

    /* 3. Workspace Card */
    .workspace-header {
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }

    .workspace-title {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .qa-count-pill {
        font-size: 12px;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 2px 9px;
        border-radius: 999px;
    }

    .workspace-subtitle {
        font-size: 12.5px;
        color: #64748b;
        margin: 3px 0 0;
    }

    .workspace-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Buttons */
    .qa-btn-primary {
        border: none;
        padding: 9px 16px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
        color: #ffffff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.15s, transform 0.1s;
    }
    .qa-btn-primary.indigo { background: #4f46e5; }
    .qa-btn-primary.indigo:hover { background: #4338ca; }
    .qa-btn-primary.blue { background: #2563eb; }
    .qa-btn-primary.blue:hover { background: #1d4ed8; }
    .qa-btn-primary.green { background: #16a34a; }
    .qa-btn-primary.green:hover { background: #15803d; }
    .qa-btn-primary.dark { background: #0f172a; }
    .qa-btn-primary.dark:hover { background: #1e293b; }

    .speed-tag {
        font-size: 10.5px;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.2);
        padding: 1px 6px;
        border-radius: 4px;
    }

    .qa-btn-secondary {
        background: #ffffff;
        color: #0f172a;
        border: 1px solid #cbd5e1;
        padding: 9px 14px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background-color 0.15s, border-color 0.15s;
    }
    .qa-btn-secondary:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }
    .qa-btn-secondary.sm {
        padding: 6px 11px;
        font-size: 12px;
        border-radius: 7px;
    }

    .qa-btn-outline-blue {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #93c5fd;
        padding: 9px 14px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background-color 0.15s;
    }
    .qa-btn-outline-blue:hover {
        background: #dbeafe;
    }
    .qa-btn-outline-blue.sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 7px;
    }
    .qa-btn-outline-blue.full {
        width: 100%;
    }

    /* Quick Inline Add Bar */
    .quick-add-bar {
        padding: 10px 24px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .quick-add-input-wrap {
        flex: 1;
        min-width: 240px;
        position: relative;
        display: flex;
        align-items: center;
    }

    .quick-add-icon {
        position: absolute;
        left: 12px;
        color: #2563eb;
        font-size: 14px;
        pointer-events: none;
    }

    .quick-add-input {
        width: 100%;
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 7px 12px 7px 34px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .quick-add-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .quick-add-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .quick-add-select {
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        padding: 6px 10px;
        cursor: pointer;
    }

    /* Filter & Search Toolbar */
    .workspace-toolbar {
        padding: 12px 24px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .filter-tabs {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .tc-filter-btn {
        background: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 5px 11px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s;
    }

    .tc-filter-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .tc-filter-btn.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }
    .status-dot.not-run { background: #94a3b8; }
    .status-dot.pass { background: #16a34a; }
    .status-dot.fail { background: #dc2626; }
    .status-dot.blocked { background: #d97706; }

    .filter-count {
        font-size: 11px;
        font-weight: 700;
        background: rgba(100, 116, 139, 0.14);
        padding: 1px 6px;
        border-radius: 999px;
    }

    .tc-filter-btn.active .filter-count {
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
    }

    .filter-count.ai {
        background: #fae8ff;
        color: #86198f;
    }

    .search-input-wrap {
        position: relative;
        width: 250px;
    }

    .search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        color: #94a3b8;
        pointer-events: none;
    }

    .search-input {
        width: 100%;
        font-family: inherit;
        font-size: 12.5px;
        padding: 6px 26px 6px 30px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        color: #0f172a;
    }

    .search-input:focus {
        outline: none;
        border-color: #2563eb;
    }

    .search-clear-btn {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #64748b;
        font-size: 15px;
        cursor: pointer;
        padding: 0 4px;
    }

    /* Bulk Action Bar */
    .bulk-action-bar {
        background: #0f172a;
        color: #ffffff;
        padding: 10px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .bulk-left, .bulk-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .bulk-count-badge {
        background: #2563eb;
        color: #ffffff;
        font-size: 12px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
    }

    .bulk-label {
        font-size: 12.5px;
        color: #cbd5e1;
        font-weight: 500;
    }

    .bulk-status-btns {
        display: inline-flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .bulk-st-btn {
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        color: #ffffff;
    }
    .bulk-st-btn.pass { background: #15803d; }
    .bulk-st-btn.fail { background: #b91c1c; }
    .bulk-st-btn.blocked { background: #b45309; }
    .bulk-st-btn.not-run { background: #334155; }

    .bulk-act-btn {
        background: #1e293b;
        color: #f8fafc;
        border: 1px solid #334155;
        padding: 5px 11px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .bulk-act-btn.ai { background: #86198f; border-color: #a21caf; }
    .bulk-act-btn.danger { background: #dc2626; border-color: #ef4444; }

    .bulk-cancel-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        padding: 4px 8px;
    }

    /* Test Cases Table */
    .tc-table-container {
        overflow-x: auto;
    }

    .tc-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .tc-table thead tr {
        background: #f8fafc;
        border-bottom: 1.5px solid #e2e8f0;
    }

    .tc-table th {
        padding: 11px 14px;
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .tc-table td {
        padding: 11px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .tc-row {
        background: #ffffff;
        transition: background-color 0.12s;
    }

    .tc-row.is-ai-draft {
        background: #fdf4ff;
    }

    .tc-row:hover td {
        background: #f8fafc;
    }

    .tc-row.drawer-active td {
        background: #eff6ff !important;
    }

    .tc-checkbox {
        width: 15px;
        height: 15px;
        accent-color: #2563eb;
        cursor: pointer;
    }

    .tc-id-btn {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #1d4ed8;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .tc-id-btn:hover {
        background: #dbeafe;
        border-color: #93c5fd;
    }

    .tc-scenario-wrap {
        cursor: pointer;
    }

    .tc-title-cell {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
        margin-bottom: 4px;
    }

    .tc-scenario-wrap:hover .tc-title-cell {
        color: #2563eb;
    }

    .tc-submeta-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .ai-review-pill {
        font-size: 11px;
        font-weight: 700;
        background: #fae8ff;
        color: #86198f;
        border: 1px solid #e879f9;
        padding: 1px 4px 1px 7px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .ai-inline-approve-btn {
        background: #a21caf;
        color: #ffffff;
        border: none;
        border-radius: 4px;
        padding: 1px 6px;
        font-size: 10.5px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }

    .ai-inline-approve-btn:hover {
        background: #86198f;
    }

    .tc-meta-tag, .tc-step-count-tag, .tc-module-tag {
        font-size: 11px;
        font-weight: 600;
        color: #475569;
        background: #f1f5f9;
        padding: 1px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .tc-precond-preview {
        font-size: 11px;
        color: #64748b;
    }

    /* Priority Select in Table */
    .tc-prio-select {
        font-family: inherit;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        cursor: pointer;
        width: 105px;
    }
    .tc-prio-select.prio-critical { color: #dc2626; background: #fef2f2; border-color: #fecaca; }
    .tc-prio-select.prio-high { color: #c2410c; background: #fff7ed; border-color: #fed7aa; }
    .tc-prio-select.prio-medium { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
    .tc-prio-select.prio-low { color: #475569; background: #f8fafc; border-color: #cbd5e1; }

    /* Status Select in Table */
    .tc-status-select {
        font-family: inherit;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 8px;
        border-radius: 7px;
        border: 1.5px solid #cbd5e1;
        cursor: pointer;
        width: 136px;
    }
    .tc-status-select.st-pass { background: #dcfce7; color: #166534; border-color: #86efac; }
    .tc-status-select.st-fail { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .tc-status-select.st-blocked { background: #fef3c7; color: #92400e; border-color: #fde047; }
    .tc-status-select.st-n-a { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .tc-status-select.st-not-executed { background: #f8fafc; color: #334155; border-color: #cbd5e1; }

    /* Bug Cell */
    .bug-ticket-pill {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11.5px;
        font-weight: 700;
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }

    .btn-log-bug-inline {
        background: #dc2626;
        color: #ffffff;
        border: none;
        padding: 4px 9px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-log-bug-inline:hover {
        background: #b91c1c;
    }

    .empty-dash {
        color: #cbd5e1;
        font-size: 12px;
    }

    /* Row Actions */
    .row-action-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .row-act-btn {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        transition: all 0.15s;
    }
    .row-act-btn:hover { background: #f1f5f9; color: #0f172a; }
    .row-act-btn.primary { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .row-act-btn.primary:hover { background: #dbeafe; }
    .row-act-btn.danger { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
    .row-act-btn.danger:hover { background: #fee2e2; }

    /* Empty State */
    .empty-table-cell {
        padding: 48px 20px !important;
        text-align: center;
    }

    .empty-state-box {
        max-width: 520px;
        margin: 0 auto;
    }

    .empty-state-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #eff6ff;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 12px;
    }

    .empty-state-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px;
    }

    .empty-state-desc {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 18px;
    }

    .empty-state-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Bugs Card */
    .bugs-card-header {
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .bugs-card-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .bugs-card-subtitle {
        font-size: 12px;
        color: #64748b;
        margin: 2px 0 0;
    }

    /* 5. SIDE DRAWER STYLES */
    .qa-drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(2px);
        z-index: 1040;
    }

    .qa-side-drawer {
        position: fixed;
        top: 0;
        right: 0;
        width: 680px;
        max-width: 96vw;
        height: 100vh;
        background: #ffffff;
        z-index: 1050;
        box-shadow: -8px 0 30px rgba(15, 23, 42, 0.16);
        transform: translateX(100%);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }

    .qa-drawer-header {
        padding: 14px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-shrink: 0;
    }

    .qa-tcid-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 13px;
        font-weight: 800;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 4px 10px;
        border-radius: 7px;
    }

    .drawer-nav-group {
        display: inline-flex;
        gap: 4px;
    }

    .drawer-icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 7px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12.5px;
        transition: all 0.15s;
    }
    .drawer-icon-btn:hover { background: #f1f5f9; color: #0f172a; }
    .drawer-icon-btn.danger { color: #dc2626; border-color: #fecaca; }
    .drawer-icon-btn.danger:hover { background: #fee2e2; }

    .drawer-autosave-pill {
        font-size: 12px;
        font-weight: 600;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .drawer-ai-banner {
        background: #fdf4ff;
        border-bottom: 1px solid #f0abfc;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    .btn-approve-ai-sm {
        background: #a21caf;
        color: #ffffff;
        border: none;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .qa-drawer-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .drawer-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .drawer-sublabel {
        display: block;
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 4px;
    }

    .qa-input {
        width: 100%;
        font-family: inherit;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 11px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .qa-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .drawer-title-input {
        font-size: 14px;
        font-weight: 700;
        line-height: 1.4;
    }

    .drawer-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
    }

    .drawer-steps-box {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
    }

    .drawer-steps-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .drawer-steps-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        padding: 8px 8px;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
    }

    .drawer-steps-table td {
        padding: 6px 6px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .drawer-details-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
    }

    .drawer-details-summary {
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: space-between;
        user-select: none;
    }

    .drawer-details-hint {
        font-size: 11.5px;
        font-weight: 500;
        color: #64748b;
    }

    .drawer-details-body {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
    }

    .drawer-result-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
    }

    .btn-quick-bug-sm {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
        padding: 3px 9px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
    }

    .qa-drawer-footer {
        padding: 12px 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    /* 6. CUSTOM OVERLAY MODALS (AI & CSV/TEMPLATE) */
    .qa-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(3px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 14px;
        overflow-y: auto;
    }

    .qa-modal-dialog {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.22);
        width: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }
    .qa-modal-xl { max-width: 920px; }
    .qa-modal-lg { max-width: 820px; }

    .qa-modal-header {
        padding: 18px 24px;
        background: #0f172a;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-shrink: 0;
    }

    .qa-modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .qa-modal-icon.indigo { background: rgba(99, 102, 241, 0.2); color: #a5b4fc; }
    .qa-modal-icon.blue { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }

    .qa-modal-title {
        font-size: 16.5px;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }

    .qa-modal-subtitle {
        font-size: 12px;
        color: #94a3b8;
        margin: 2px 0 0;
    }

    .qa-modal-close {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.08);
        color: #cbd5e1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }
    .qa-modal-close:hover {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
    }

    .qa-modal-tabs {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0 24px;
        display: flex;
        gap: 18px;
        flex-shrink: 0;
    }

    .qa-modal-tab {
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 12px 4px;
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .qa-modal-tab.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }

    .qa-modal-body {
        padding: 22px 24px;
        overflow-y: auto;
        flex: 1;
    }

    .qa-modal-footer-inline {
        padding-top: 16px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .ai-config-bar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .qa-select-compact {
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        padding: 6px 10px;
    }

    .qa-textarea {
        width: 100%;
        font-family: inherit;
        font-size: 13.5px;
        line-height: 1.5;
        color: #0f172a;
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        padding: 12px 14px;
        resize: vertical;
    }
    .qa-textarea:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .qa-chip-btn {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        cursor: pointer;
    }
    .qa-chip-btn:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .ai-review-callout {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .template-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }

    .template-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 10px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .template-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.06);
    }

    .template-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .template-icon-badge {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }
    .template-icon-badge.indigo { background: #eef2ff; color: #4f46e5; }
    .template-icon-badge.emerald { background: #f0fdf4; color: #16a34a; }
    .template-icon-badge.amber { background: #fffbeb; color: #d97706; }
    .template-icon-badge.rose { background: #fef2f2; color: #dc2626; }

    .template-card-title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .template-card-desc {
        font-size: 12px;
        color: #64748b;
        margin: 0;
        line-height: 1.45;
    }

    .csv-format-banner {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .csv-dropzone {
        border: 2px dashed #94a3b8;
        border-radius: 12px;
        padding: 32px 20px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.15s;
    }
    .csv-dropzone:hover {
        background: #eff6ff;
        border-color: #2563eb;
    }

    .csv-dropzone-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #dbeafe;
        color: #1d4ed8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 10px;
    }

    /* Toast */
    .qa-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #0f172a;
        color: #ffffff;
        padding: 12px 18px;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
        font-size: 13px;
        font-weight: 600;
        z-index: 10000;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    /* Responsive Adjustments */
    @media (max-width: 1024px) {
        .kpi-strip-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .kpi-box:nth-child(2) {
            border-right: none;
        }
        .kpi-box:nth-child(1),
        .kpi-box:nth-child(2) {
            border-bottom: 1px solid #e2e8f0;
        }
    }

    @media (max-width: 768px) {
        .kpi-strip-grid {
            grid-template-columns: 1fr;
        }
        .kpi-box {
            border-right: none;
            border-bottom: 1px solid #e2e8f0;
        }
        .kpi-box:last-child {
            border-bottom: none;
        }
        .verdict-box {
            align-items: flex-start;
        }
        .template-grid-2 {
            grid-template-columns: 1fr;
        }
        .drawer-meta-grid {
            grid-template-columns: 1fr;
        }
        .search-input-wrap {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    const REPORT_ID = {{ $report->id }};
    const PROJECT_ID = {{ $report->project_id ?? 'null' }};
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const PROJECT_CODE = '{{ $report->project->project_code ?? "TC" }}';

    let currentOpenTc = null;
    let autosaveTimer = null;
    let generatedAiDraftsCache = [];
    let toastTimer = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById('qaToast');
        const icon = document.getElementById('qaToastIcon');
        const msg = document.getElementById('qaToastMessage');
        if (!toast) return;

        msg.textContent = message;
        if (type === 'error') {
            icon.className = 'fas fa-exclamation-circle';
            icon.style.color = '#f87171';
        } else {
            icon.className = 'fas fa-check-circle';
            icon.style.color = '#4ade80';
        }

        toast.style.display = 'inline-flex';
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.style.display = 'none';
        }, 3200);
    }

    // ──────────────────────────────────────────────────────
    // 1. SIDE DRAWER CONTROLLER & PREV/NEXT NAVIGATION
    // ──────────────────────────────────────────────────────

    function openDrawerForId(id) {
        const row = document.getElementById('tcRow-' + id);
        if (!row || !row.dataset.tc) return;

        // Flush pending autosave if switching from another open test case
        if (autosaveTimer && currentOpenTc && currentOpenTc.id !== id) {
            clearTimeout(autosaveTimer);
            saveDrawerChangesImmediately();
        }

        const tcData = JSON.parse(row.dataset.tc);
        openTestCaseDrawer(tcData);
    }

    function openTestCaseDrawer(tc) {
        currentOpenTc = tc;

        // Highlight active row in table
        document.querySelectorAll('.tc-row').forEach(r => {
            r.classList.toggle('drawer-active', parseInt(r.dataset.id, 10) === parseInt(tc.id, 10));
        });

        document.getElementById('drawerTcInternalId').value = tc.id;
        document.getElementById('drawerTcIdBadge').textContent = tc.test_case_id || ('TC-' + tc.id);
        document.getElementById('drawerTitle').value = tc.title || '';
        document.getElementById('drawerPriority').value = tc.priority || 'Medium';

        const dyn = tc.dynamic_fields || {};
        document.getElementById('drawerType').value = dyn.caseType || 'Positive';
        document.getElementById('drawerModule').value = dyn.moduleFeature || '';
        document.getElementById('drawerRefReq').value = dyn.refRequirement || '';
        document.getElementById('drawerPrecondition').value = tc.precondition || '';

        // Auto-open precondition details if non-empty
        const precondDetails = document.getElementById('drawerPrecondDetails');
        if (precondDetails) {
            precondDetails.open = Boolean(tc.precondition || dyn.moduleFeature || dyn.refRequirement);
        }

        const normSt = normalizeStatus(tc.status);
        document.getElementById('drawerStatus').value = normSt;

        document.getElementById('drawerActualResult').value = tc.actual_result || '';
        document.getElementById('drawerEvidenceUrl').value = tc.evidence_url || '';
        document.getElementById('drawerBugTicket').value = tc.bug_ticket || '';
        document.getElementById('drawerNotesQa').value = tc.notes_qa || '';
        document.getElementById('drawerNotesProg').value = tc.notes_programmer || '';

        // Quick Bug Button visibility
        const btnBug = document.getElementById('btnDrawerQuickBug');
        btnBug.style.display = (normSt === 'Fail') ? 'inline-block' : 'none';

        // AI Draft Banner
        const aiBanner = document.getElementById('drawerAiBanner');
        if (tc.is_ai_generated && tc.review_status === 'needs_review') {
            aiBanner.style.display = 'flex';
        } else {
            aiBanner.style.display = 'none';
        }

        renderDrawerSteps(tc.steps || []);

        const drawer = document.getElementById('tcSideDrawer');
        const backdrop = document.getElementById('drawerBackdrop');
        backdrop.style.display = 'block';
        requestAnimationFrame(() => {
            drawer.style.transform = 'translateX(0)';
        });

        setAutosaveBadge('saved');
    }

    function navigateDrawer(direction) {
        if (!currentOpenTc) return;
        const visibleRows = Array.from(document.querySelectorAll('#testCaseTableBody tr.tc-row'))
            .filter(r => r.style.display !== 'none');
        const currentIdx = visibleRows.findIndex(r => parseInt(r.dataset.id, 10) === parseInt(currentOpenTc.id, 10));
        if (currentIdx === -1) return;

        const targetIdx = currentIdx + direction;
        if (targetIdx >= 0 && targetIdx < visibleRows.length) {
            const targetId = parseInt(visibleRows[targetIdx].dataset.id, 10);
            openDrawerForId(targetId);
        }
    }

    function closeTestCaseDrawer() {
        const drawer = document.getElementById('tcSideDrawer');
        const backdrop = document.getElementById('drawerBackdrop');

        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
            saveDrawerChangesImmediately();
        }

        document.querySelectorAll('.tc-row.drawer-active').forEach(r => r.classList.remove('drawer-active'));

        drawer.style.transform = 'translateX(100%)';
        setTimeout(() => {
            backdrop.style.display = 'none';
            currentOpenTc = null;
        }, 250);
    }

    // Global Keyboard Shortcuts (ESC to close modals/drawer, Alt+Up/Down to navigate drawer)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('aiGenerateModal').style.display !== 'none') {
                closeAiGenerateModal();
            } else if (document.getElementById('importTemplateModal').style.display !== 'none') {
                closeImportTemplateModal();
            } else if (currentOpenTc) {
                closeTestCaseDrawer();
            }
        } else if (currentOpenTc && e.altKey && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
            e.preventDefault();
            navigateDrawer(e.key === 'ArrowUp' ? -1 : 1);
        }
    });

    // ──────────────────────────────────────────────────────
    // 2. STEPS MINI-TABLE INSIDE DRAWER
    // ──────────────────────────────────────────────────────

    function renderDrawerSteps(steps) {
        const tbody = document.getElementById('drawerStepsTableBody');
        tbody.innerHTML = '';

        const badge = document.getElementById('drawerStepCountBadge');
        badge.textContent = steps.length + ' langkah';

        const noStepsHint = document.getElementById('drawerNoStepsHint');
        if (steps.length === 0) {
            noStepsHint.style.display = 'block';
            return;
        }
        noStepsHint.style.display = 'none';

        steps.forEach((s, idx) => {
            tbody.appendChild(buildDrawerStepRowElement(idx + 1, s));
        });
    }

    function buildDrawerStepRowElement(num, s = {}) {
        const tr = document.createElement('tr');
        const stVal = s.status || 'Not Executed';
        tr.innerHTML = `
            <td style="text-align: center; color: #64748b; font-weight: 700;">${num}</td>
            <td>
                <input type="text" class="qa-input step-desc-input" value="${escapeHtml(s.step_description || s.step || '')}"
                       placeholder="Aksi langkah uji..."
                       style="font-size: 12px; padding: 5px 8px;"
                       oninput="onDrawerFieldChange()" />
            </td>
            <td>
                <input type="text" class="qa-input step-exp-input" value="${escapeHtml(s.expected_result || s.expected || '')}"
                       placeholder="Ekspektasi hasil..."
                       style="font-size: 12px; padding: 5px 8px;"
                       oninput="onDrawerFieldChange()"
                       onkeydown="if(event.key==='Enter'){ event.preventDefault(); addDrawerStepRow(); }" />
            </td>
            <td>
                <input type="text" class="qa-input step-act-input" value="${escapeHtml(s.actual_result || s.actual || '')}"
                       placeholder="Fakta aktual..."
                       style="font-size: 12px; padding: 5px 8px;"
                       oninput="onDrawerFieldChange()" />
            </td>
            <td>
                <select class="qa-input step-status-select" style="font-size: 11px; font-weight: 700; padding: 5px 6px;" onchange="onDrawerFieldChange()">
                    <option value="Not Executed" ${stVal === 'Not Executed' ? 'selected' : ''}>Not Executed</option>
                    <option value="Pass" ${stVal === 'Pass' ? 'selected' : ''}>Pass</option>
                    <option value="Fail" ${stVal === 'Fail' ? 'selected' : ''}>Fail</option>
                    <option value="Blocked" ${stVal === 'Blocked' ? 'selected' : ''}>Blocked</option>
                </select>
            </td>
            <td style="text-align: center;">
                <button type="button" onclick="removeDrawerStepRow(this)" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 15px; padding: 2px 4px;" title="Hapus langkah">&times;</button>
            </td>
        `;
        return tr;
    }

    function addDrawerStepRow() {
        const tbody = document.getElementById('drawerStepsTableBody');
        const count = tbody.querySelectorAll('tr').length + 1;
        const tr = buildDrawerStepRowElement(count, {});
        tbody.appendChild(tr);

        document.getElementById('drawerNoStepsHint').style.display = 'none';
        document.getElementById('drawerStepCountBadge').textContent = count + ' langkah';

        tr.querySelector('.step-desc-input').focus();
        onDrawerFieldChange();
    }

    function removeDrawerStepRow(btn) {
        const tr = btn.closest('tr');
        tr.remove();
        const rows = document.querySelectorAll('#drawerStepsTableBody tr');
        rows.forEach((r, i) => {
            r.cells[0].textContent = i + 1;
        });
        document.getElementById('drawerStepCountBadge').textContent = rows.length + ' langkah';
        if (rows.length === 0) {
            document.getElementById('drawerNoStepsHint').style.display = 'block';
        }
        onDrawerFieldChange();
    }

    function collectDrawerSteps() {
        const rows = document.querySelectorAll('#drawerStepsTableBody tr');
        const steps = [];
        rows.forEach(r => {
            const desc = r.querySelector('.step-desc-input')?.value.trim() || '';
            const exp  = r.querySelector('.step-exp-input')?.value.trim() || '';
            const act  = r.querySelector('.step-act-input')?.value.trim() || '';
            const st   = r.querySelector('.step-status-select')?.value || 'Not Executed';
            if (desc !== '') {
                steps.push({
                    step_description: desc,
                    expected_result: exp,
                    actual_result: act,
                    status: st
                });
            }
        });
        return steps;
    }

    // ──────────────────────────────────────────────────────
    // 3. AUTOSAVE ENGINE
    // ──────────────────────────────────────────────────────

    function onDrawerFieldChange() {
        setAutosaveBadge('saving');

        const statusVal = document.getElementById('drawerStatus').value;
        const btnBug = document.getElementById('btnDrawerQuickBug');
        btnBug.style.display = (statusVal === 'Fail') ? 'inline-block' : 'none';

        if (autosaveTimer) clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(saveDrawerChangesImmediately, 450);
    }

    function setAutosaveBadge(state) {
        const statusEl = document.getElementById('drawerAutosaveStatus');
        if (state === 'saving') {
            statusEl.style.color = '#2563eb';
            statusEl.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        } else if (state === 'saved') {
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            statusEl.style.color = '#15803d';
            statusEl.innerHTML = `<i class="fas fa-check-circle"></i> Tersimpan (${time})`;
        } else if (state === 'error') {
            statusEl.style.color = '#dc2626';
            statusEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Gagal menyimpan';
        }
    }

    async function saveDrawerChangesImmediately() {
        if (!currentOpenTc) return;

        const id = document.getElementById('drawerTcInternalId').value;
        const title = document.getElementById('drawerTitle').value.trim();
        if (!title) return;

        const payload = {
            title: title,
            priority: document.getElementById('drawerPriority').value,
            status: document.getElementById('drawerStatus').value,
            precondition: document.getElementById('drawerPrecondition').value,
            actual_result: document.getElementById('drawerActualResult').value,
            evidence_url: document.getElementById('drawerEvidenceUrl').value,
            bug_ticket: document.getElementById('drawerBugTicket').value,
            notes_qa: document.getElementById('drawerNotesQa').value,
            notes_programmer: document.getElementById('drawerNotesProg').value,
            dynamic_fields: {
                caseType: document.getElementById('drawerType').value,
                moduleFeature: document.getElementById('drawerModule').value,
                refRequirement: document.getElementById('drawerRefReq').value
            },
            steps: collectDrawerSteps()
        };

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await res.json();
            if (result.success) {
                setAutosaveBadge('saved');
                currentOpenTc = result.data;
                updateTableRowVisuals(result.data);
                if (result.qg) updateQualityGateUI(result.qg);
            } else {
                setAutosaveBadge('error');
            }
        } catch (e) {
            console.error('Autosave error:', e);
            setAutosaveBadge('error');
        }
    }

    function updateTableRowVisuals(tc) {
        const row = document.getElementById('tcRow-' + tc.id);
        if (!row) return;

        const normSt = normalizeStatus(tc.status);
        const normPrio = ['Critical', 'High', 'Medium', 'Low'].includes(tc.priority) ? tc.priority : 'Medium';

        row.dataset.tc = JSON.stringify(tc);
        row.dataset.status = normSt;

        // Title
        const titleEl = row.querySelector('.tc-title-cell');
        if (titleEl) titleEl.textContent = tc.title;

        // Step count tag
        const stepCountEl = row.querySelector('.step-num-text');
        if (stepCountEl && tc.steps) {
            stepCountEl.textContent = `${tc.steps.length} langkah`;
        }

        // Priority Select
        const prioSelect = row.querySelector('.tc-prio-select');
        if (prioSelect) {
            prioSelect.value = normPrio;
            prioSelect.className = `tc-prio-select prio-${normPrio.toLowerCase()}`;
        }

        // Status Select
        const statusSelect = row.querySelector('.tc-status-select');
        if (statusSelect) {
            statusSelect.value = normSt;
            statusSelect.className = `tc-status-select st-${normSt.toLowerCase().replace(/[\s\/]/g, '-')}`;
        }

        // Bug Cell
        const bugCell = row.querySelector('.tc-bug-cell');
        if (bugCell) {
            if (tc.bug_ticket) {
                bugCell.innerHTML = `<span class="bug-ticket-pill" onclick="openDrawerForId(${tc.id})" title="Klik untuk edit tiket bug"><i class="fas fa-bug"></i> ${escapeHtml(tc.bug_ticket)}</span>`;
            } else if (normSt === 'Fail') {
                bugCell.innerHTML = `<button type="button" class="btn-log-bug-inline" onclick="openQuickBugForTcId(${tc.id})"><i class="fas fa-plus"></i> Catat Bug</button>`;
            } else {
                bugCell.innerHTML = '<span class="empty-dash">-</span>';
            }
        }
    }

    // ──────────────────────────────────────────────────────
    // 4. INLINE TABLE STATUS & PRIORITY UPDATES
    // ──────────────────────────────────────────────────────

    async function onInlineStatusChange(tcId, newStatus, selectEl) {
        selectEl.className = `tc-status-select st-${newStatus.toLowerCase().replace(/[\s\/]/g, '-')}`;
        const row = document.getElementById('tcRow-' + tcId);
        if (row) row.dataset.status = newStatus;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/${tcId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });

            const result = await res.json();
            if (result.success) {
                updateTableRowVisuals(result.data);
                if (currentOpenTc && parseInt(currentOpenTc.id, 10) === parseInt(tcId, 10)) {
                    document.getElementById('drawerStatus').value = newStatus;
                }
                if (result.qg) updateQualityGateUI(result.qg);
            }
        } catch (e) {
            console.error('Failed inline status update:', e);
        }
    }

    async function onInlinePriorityChange(tcId, newPriority, selectEl) {
        selectEl.className = `tc-prio-select prio-${newPriority.toLowerCase()}`;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/${tcId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ priority: newPriority })
            });

            const result = await res.json();
            if (result.success) {
                updateTableRowVisuals(result.data);
                if (currentOpenTc && parseInt(currentOpenTc.id, 10) === parseInt(tcId, 10)) {
                    document.getElementById('drawerPriority').value = newPriority;
                }
            }
        } catch (e) {
            console.error('Failed inline priority update:', e);
        }
    }

    // ──────────────────────────────────────────────────────
    // 5. CARA 3: TAMBAH MANUAL (QUICK INLINE BAR & DRAWER)
    // ──────────────────────────────────────────────────────

    function focusQuickAddOrOpenDrawer() {
        const quickInput = document.getElementById('quickAddTitleInput');
        if (quickInput.value.trim() !== '') {
            submitQuickInlineAdd(true);
        } else {
            quickInput.focus();
            quickInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    async function submitQuickInlineAdd(openDrawerAfter = false) {
        const titleInput = document.getElementById('quickAddTitleInput');
        const prioSelect = document.getElementById('quickAddPrioritySelect');
        const nextNum = document.querySelectorAll('#testCaseTableBody tr.tc-row').length + 1;
        const autoTcId = `${PROJECT_CODE}-TC-${String(nextNum).padStart(3, '0')}`;

        const rawTitle = titleInput.value.trim();
        const finalTitle = rawTitle || `Skenario Pengujian ${nextNum}`;

        const btn = document.getElementById('btnQuickAddSave');
        btn.disabled = true;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: finalTitle,
                    test_case_id: autoTcId,
                    priority: prioSelect.value || 'Medium',
                    status: 'Not Executed',
                    steps: []
                })
            });

            const result = await res.json();
            if (result.success) {
                titleInput.value = '';
                const emptyRow = document.getElementById('emptyTestCasesRow');
                if (emptyRow) emptyRow.remove();

                appendTestCaseRowToTable(result.data);
                if (result.qg) updateQualityGateUI(result.qg);

                if (openDrawerAfter || !rawTitle) {
                    openTestCaseDrawer(result.data);
                    setTimeout(() => {
                        const dt = document.getElementById('drawerTitle');
                        dt.focus();
                        if (!rawTitle) dt.select();
                    }, 260);
                } else {
                    showToast(`Test case ${result.data.test_case_id} ditambahkan`);
                    titleInput.focus();
                }
            }
        } catch (e) {
            console.error('Failed to add test case:', e);
            showToast('Gagal menambahkan test case', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // ──────────────────────────────────────────────────────
    // 6. SINGLE DUPLICATE, DELETE & AI APPROVE
    // ──────────────────────────────────────────────────────

    async function duplicateSingleTc(id) {
        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids: [id] })
            });

            const result = await res.json();
            if (result.success && result.data.length > 0) {
                result.data.forEach(tc => appendTestCaseRowToTable(tc));
                if (result.qg) updateQualityGateUI(result.qg);
                showToast('Test case berhasil diduplikat');
            }
        } catch (e) {
            console.error('Error duplicating:', e);
        }
    }

    function duplicateCurrentDrawerTc() {
        if (!currentOpenTc) return;
        duplicateSingleTc(currentOpenTc.id);
    }

    async function deleteSingleTc(id) {
        if (!confirm('Hapus test case ini dari report?')) return;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });

            const result = await res.json();
            if (result.success) {
                const row = document.getElementById('tcRow-' + id);
                if (row) row.remove();
                if (result.qg) updateQualityGateUI(result.qg);
                updateCounts();
                showToast('Test case dihapus');
            }
        } catch (e) {
            console.error('Error deleting:', e);
        }
    }

    function deleteCurrentDrawerTc() {
        if (!currentOpenTc) return;
        const id = currentOpenTc.id;
        closeTestCaseDrawer();
        deleteSingleTc(id);
    }

    async function approveSingleAiTc(id) {
        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/${id}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });

            const result = await res.json();
            if (result.success) {
                const row = document.getElementById('tcRow-' + id);
                if (row) {
                    row.classList.remove('is-ai-draft');
                    row.dataset.aiReview = 'reviewed';
                    const pill = row.querySelector('.ai-review-pill');
                    if (pill) pill.remove();
                    if (row.dataset.tc) {
                        const parsed = JSON.parse(row.dataset.tc);
                        parsed.review_status = 'reviewed';
                        row.dataset.tc = JSON.stringify(parsed);
                    }
                }
                if (currentOpenTc && parseInt(currentOpenTc.id, 10) === parseInt(id, 10)) {
                    document.getElementById('drawerAiBanner').style.display = 'none';
                    currentOpenTc.review_status = 'reviewed';
                }
                updateCounts();
                showToast('Draft AI disetujui');
            }
        } catch (e) {
            console.error('Error approving AI draft:', e);
        }
    }

    function approveCurrentDrawerTc() {
        if (!currentOpenTc) return;
        approveSingleAiTc(currentOpenTc.id);
    }

    // ──────────────────────────────────────────────────────
    // 7. BULK ACTIONS (AKSI MASSAL)
    // ──────────────────────────────────────────────────────

    function getSelectedTcIds() {
        const checked = document.querySelectorAll('.tc-row-check:checked');
        return Array.from(checked).map(c => parseInt(c.value, 10));
    }

    function onTcRowCheckChange() {
        const selected = getSelectedTcIds();
        const bar = document.getElementById('bulkActionBar');
        const badge = document.getElementById('bulkSelectedCountBadge');

        if (selected.length > 0) {
            bar.style.display = 'flex';
            badge.textContent = `${selected.length} dipilih`;
        } else {
            bar.style.display = 'none';
            document.getElementById('selectAllTcCheck').checked = false;
        }
    }

    function toggleSelectAllTc(checked) {
        document.querySelectorAll('.tc-row-check').forEach(cb => {
            const row = cb.closest('tr');
            if (row && row.style.display !== 'none') {
                cb.checked = checked;
            }
        });
        onTcRowCheckChange();
    }

    function clearBulkSelection() {
        document.querySelectorAll('.tc-row-check').forEach(cb => cb.checked = false);
        document.getElementById('selectAllTcCheck').checked = false;
        onTcRowCheckChange();
    }

    async function executeBulkStatusDirect(newStatus) {
        const ids = getSelectedTcIds();
        if (ids.length === 0) return;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/bulk-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids: ids, status: newStatus })
            });

            const result = await res.json();
            if (result.success) {
                ids.forEach(id => {
                    const row = document.getElementById('tcRow-' + id);
                    if (row) {
                        row.dataset.status = newStatus;
                        const selectEl = row.querySelector('.tc-status-select');
                        if (selectEl) {
                            selectEl.value = newStatus;
                            selectEl.className = `tc-status-select st-${newStatus.toLowerCase().replace(/[\s\/]/g, '-')}`;
                        }
                        if (row.dataset.tc) {
                            const tcObj = JSON.parse(row.dataset.tc);
                            tcObj.status = newStatus;
                            updateTableRowVisuals(tcObj);
                        }
                    }
                });
                clearBulkSelection();
                if (result.qg) updateQualityGateUI(result.qg);
                showToast(`Status ${ids.length} test case diubah ke ${newStatus}`);
            }
        } catch (e) {
            console.error('Bulk status error:', e);
        }
    }

    async function executeBulkDelete() {
        const ids = getSelectedTcIds();
        if (ids.length === 0) return;
        if (!confirm(`Hapus ${ids.length} test case terpilih secara permanen?`)) return;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/bulk-delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            });

            const result = await res.json();
            if (result.success) {
                ids.forEach(id => {
                    const row = document.getElementById('tcRow-' + id);
                    if (row) row.remove();
                });
                clearBulkSelection();
                if (result.qg) updateQualityGateUI(result.qg);
                updateCounts();
                showToast(`${ids.length} test case dihapus`);
            }
        } catch (e) {
            console.error('Bulk delete error:', e);
        }
    }

    async function executeBulkDuplicate() {
        const ids = getSelectedTcIds();
        if (ids.length === 0) return;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            });

            const result = await res.json();
            if (result.success && result.data.length > 0) {
                result.data.forEach(tc => appendTestCaseRowToTable(tc));
                clearBulkSelection();
                if (result.qg) updateQualityGateUI(result.qg);
                showToast(`${result.data.length} test case berhasil diduplikat`);
            }
        } catch (e) {
            console.error('Bulk duplicate error:', e);
        }
    }

    async function executeBulkApproveAi() {
        const ids = getSelectedTcIds();
        if (ids.length === 0) return;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/bulk-approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            });

            const result = await res.json();
            if (result.success) {
                ids.forEach(id => {
                    const row = document.getElementById('tcRow-' + id);
                    if (row) {
                        row.classList.remove('is-ai-draft');
                        row.dataset.aiReview = 'reviewed';
                        const pill = row.querySelector('.ai-review-pill');
                        if (pill) pill.remove();
                    }
                });
                clearBulkSelection();
                updateCounts();
                showToast('Draft AI terpilih telah disetujui');
            }
        } catch (e) {
            console.error('Bulk approve AI error:', e);
        }
    }

    // ──────────────────────────────────────────────────────
    // 8. CARA 1: AI GENERATE MODAL CONTROLLER
    // ──────────────────────────────────────────────────────

    function openAiGenerateModal() {
        document.getElementById('aiInputSection').style.display = 'block';
        document.getElementById('aiLoadingSection').style.display = 'none';
        document.getElementById('aiPreviewSection').style.display = 'none';
        document.getElementById('aiGenerateModal').style.display = 'flex';
        setTimeout(() => document.getElementById('aiGenRawText').focus(), 100);
    }

    function closeAiGenerateModal() {
        document.getElementById('aiGenerateModal').style.display = 'none';
    }

    function fillAiExample(type) {
        const rawEl = document.getElementById('aiGenRawText');
        if (type === 'auth') {
            rawEl.value = 'Fitur: Autentikasi Pengguna & Two-Factor Authentication (2FA).\nSebagai pengguna terdaftar, saya dapat login menggunakan email dan password. Jika akun mengaktifkan 2FA, sistem meminta 6-digit OTP dari Google Authenticator. Sistem memblokir sementara akun setelah 5 kali percobaan gagal berturut-turut selama 15 menit.';
        } else if (type === 'checkout') {
            rawEl.value = 'Fitur: Checkout Keranjang Belanja & Kode Voucher.\nPengguna dapat memilih voucher diskon persentase (maks Rp50.000) atau gratis ongkir. Sistem memastikan total tagihan tidak bernilai negatif. Jika pembayaran gagal atau kadaluarsa, stok dikembalikan otomatis.';
        } else if (type === 'upload') {
            rawEl.value = 'Fitur: Upload Dokumen KYC (KTP/SIM).\nPengguna wajib mengunggah foto identitas format JPG/PNG maksimal 3MB. Sistem menolak format selain gambar (misal PDF atau EXE). Jika upload berhasil, tampil pratinjau dokumen dan status menjadi Menunggu Verifikasi.';
        }
        rawEl.focus();
    }

    function backToAiInput() {
        document.getElementById('aiInputSection').style.display = 'block';
        document.getElementById('aiLoadingSection').style.display = 'none';
        document.getElementById('aiPreviewSection').style.display = 'none';
    }

    async function runAiGenerate() {
        const text = document.getElementById('aiGenRawText').value.trim();
        if (!text || text.length < 10) {
            showToast('Tuliskan user story atau requirement minimal 10 karakter', 'error');
            document.getElementById('aiGenRawText').focus();
            return;
        }

        const provider = document.getElementById('aiGenProvider').value;

        document.getElementById('aiInputSection').style.display = 'none';
        document.getElementById('aiLoadingSection').style.display = 'block';
        document.getElementById('aiPreviewSection').style.display = 'none';

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/ai-generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    raw_text: text,
                    provider: provider,
                    save_directly: false
                })
            });

            const result = await res.json();
            if (result.success && result.data && result.data.length > 0) {
                generatedAiDraftsCache = result.data;
                renderAiDraftReviewList(result.data);

                document.getElementById('aiLoadingSection').style.display = 'none';
                document.getElementById('aiPreviewSection').style.display = 'block';
            } else {
                showToast(result.message || 'Gagal menyusun test case dari teks tersebut', 'error');
                backToAiInput();
            }
        } catch (e) {
            console.error('AI generate error:', e);
            showToast('Gagal terhubung ke layanan AI. Periksa konfigurasi API Key.', 'error');
            backToAiInput();
        }
    }

    function renderAiDraftReviewList(drafts) {
        const container = document.getElementById('aiDraftListContainer');
        container.innerHTML = '';
        document.getElementById('aiDraftCountBadge').textContent = drafts.length;

        drafts.forEach((d, i) => {
            const card = document.createElement('div');
            card.className = 'ai-draft-card';
            card.style.cssText = 'background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;';

            const stepsHtml = (d.steps && d.steps.length > 0)
                ? d.steps.map((s, si) => `<div style="font-size: 11.5px; color: #334155; margin-top: 3px;"><strong>${si+1}.</strong> ${escapeHtml(s.step || s.step_description || '')} <span style="color:#15803d;">&rarr; ${escapeHtml(s.expected || s.expected_result || '')}</span></div>`).join('')
                : '<div style="font-size: 11px; color:#94a3b8;">Langkah dapat dilengkapi di drawer</div>';

            card.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <input type="checkbox" class="ai-draft-check" data-index="${i}" checked style="margin-top: 4px; width: 15px; height: 15px; accent-color: #2563eb; cursor: pointer;" />
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                            <span style="font-family: monospace; font-size: 11.5px; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 1px 7px; border-radius: 5px;">
                                ${escapeHtml(d.test_case_id)}
                            </span>
                            <span style="font-size: 11px; font-weight: 700; background: #fae8ff; color: #86198f; padding: 1px 7px; border-radius: 5px;">
                                Draft AI
                            </span>
                            <span style="font-size: 11.5px; font-weight: 700; color: #334155;">
                                Prioritas: ${escapeHtml(d.priority || 'Medium')}
                            </span>
                            <span style="font-size: 11.5px; color: #64748b;">
                                (${escapeHtml(d.caseType || 'Positive')})
                            </span>
                        </div>
                        <input type="text" class="qa-input ai-draft-title" value="${escapeHtml(d.title)}"
                               style="font-size: 13px; font-weight: 700; margin-bottom: 6px;" />
                        <div style="background: #f8fafc; padding: 8px 10px; border-radius: 7px; border: 1px solid #e2e8f0;">
                            ${stepsHtml}
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function toggleAllAiDraftChecks(checked) {
        document.querySelectorAll('.ai-draft-check').forEach(cb => cb.checked = checked);
    }

    async function saveSelectedAiDrafts(approved) {
        const checked = document.querySelectorAll('.ai-draft-check:checked');
        if (checked.length === 0) {
            showToast('Pilih minimal 1 test case untuk disimpan', 'error');
            return;
        }

        const btnDraft = document.getElementById('btnSaveAiAsDraft');
        const btnApprove = document.getElementById('btnSaveAiApproved');
        if (btnDraft) btnDraft.disabled = true;
        if (btnApprove) btnApprove.disabled = true;

        const selectedDrafts = [];
        checked.forEach(cb => {
            const idx = parseInt(cb.dataset.index, 10);
            const draft = generatedAiDraftsCache[idx];
            const card = cb.closest('.ai-draft-card');
            const editedTitle = card.querySelector('.ai-draft-title').value.trim();
            draft.title = editedTitle || draft.title;
            draft.review_status = approved ? 'reviewed' : 'needs_review';
            selectedDrafts.push(draft);
        });

        let successCount = 0;
        for (const d of selectedDrafts) {
            try {
                const res = await fetch(`/reports/${REPORT_ID}/test-cases`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: d.title,
                        test_case_id: d.test_case_id,
                        priority: d.priority || 'Medium',
                        status: 'Not Executed',
                        precondition: d.precondition,
                        dynamic_fields: d.dynamic_fields || { caseType: d.caseType || 'Positive' },
                        is_ai_generated: true,
                        review_status: d.review_status,
                        steps: (d.steps || []).map(s => ({
                            step_description: s.step || s.step_description || '',
                            expected_result: s.expected || s.expected_result || '',
                            status: 'Not Executed'
                        }))
                    })
                });

                const result = await res.json();
                if (result.success) {
                    successCount++;
                    const emptyRow = document.getElementById('emptyTestCasesRow');
                    if (emptyRow) emptyRow.remove();
                    appendTestCaseRowToTable(result.data);
                    if (result.qg) updateQualityGateUI(result.qg);
                }
            } catch (e) {
                console.error('Error inserting AI draft:', e);
            }
        }

        if (btnDraft) btnDraft.disabled = false;
        if (btnApprove) btnApprove.disabled = false;

        closeAiGenerateModal();
        updateCounts();
        showToast(
            approved
                ? `${successCount} test case AI disetujui & ditambahkan`
                : `${successCount} draft AI ditambahkan (Perlu Review)`
        );
    }

    // ──────────────────────────────────────────────────────
    // 9. CARA 2: CSV IMPORT & SPRINT TEMPLATES MODAL
    // ──────────────────────────────────────────────────────

    function openImportTemplateModal() {
        document.getElementById('importTemplateModal').style.display = 'flex';
    }

    function closeImportTemplateModal() {
        document.getElementById('importTemplateModal').style.display = 'none';
    }

    function switchImportTab(tab) {
        const btnTpl = document.getElementById('tabBtnTemplate');
        const btnCsv = document.getElementById('tabBtnCsv');
        const contentTpl = document.getElementById('tabContentTemplate');
        const contentCsv = document.getElementById('tabContentCsv');

        const isTpl = (tab === 'template');
        btnTpl.classList.toggle('active', isTpl);
        btnTpl.setAttribute('aria-selected', isTpl ? 'true' : 'false');
        btnCsv.classList.toggle('active', !isTpl);
        btnCsv.setAttribute('aria-selected', !isTpl ? 'true' : 'false');

        contentTpl.style.display = isTpl ? 'block' : 'none';
        contentCsv.style.display = isTpl ? 'none' : 'block';
    }

    let parsedCsvRowsCache = [];

    function handleCsvFileSelected(files) {
        if (!files || files.length === 0) return;
        const file = files[0];
        document.getElementById('csvFileName').textContent = file.name;

        const reader = new FileReader();
        reader.onload = function(e) {
            parseAndPreviewCsv(e.target.result);
        };
        reader.readAsText(file);
    }

    function parseAndPreviewCsv(csvText) {
        const lines = csvText.split(/\r\n|\n/).filter(l => l.trim() !== '');
        if (lines.length < 2) {
            showToast('File CSV minimal harus memiliki 1 baris header dan 1 baris data', 'error');
            return;
        }

        const headers = parseCsvLine(lines[0]).map(h => h.toLowerCase().trim().replace(/[\s\-_]/g, ''));
        parsedCsvRowsCache = [];

        const previewBody = document.getElementById('csvPreviewBody');
        previewBody.innerHTML = '';

        for (let i = 1; i < lines.length; i++) {
            const cols = parseCsvLine(lines[i]);
            if (cols.length === 0 || cols.every(c => c.trim() === '')) continue;

            const rowObj = {};
            headers.forEach((h, hi) => {
                rowObj[h] = cols[hi] || '';
            });
            parsedCsvRowsCache.push(rowObj);

            if (i <= 5) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding: 6px 10px; font-family: monospace; border-bottom: 1px solid #f1f5f9;">${escapeHtml(rowObj.tcid || rowObj.tc_id || ('TC-' + i))}</td>
                    <td style="padding: 6px 10px; font-weight: 600; border-bottom: 1px solid #f1f5f9;">${escapeHtml(rowObj.scenario || rowObj.title || 'Scenario')}</td>
                    <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">${escapeHtml(rowObj.priority || 'Medium')}</td>
                    <td style="padding: 6px 10px; color:#64748b; border-bottom: 1px solid #f1f5f9;">${escapeHtml(rowObj.steps || rowObj.step || '')}</td>
                `;
                previewBody.appendChild(tr);
            }
        }

        document.getElementById('csvRowCountBadge').textContent = `${parsedCsvRowsCache.length} baris`;
        document.getElementById('csvDropZone').style.display = 'none';
        document.getElementById('csvPreviewSection').style.display = 'block';
    }

    function parseCsvLine(text) {
        const result = [];
        let cur = '';
        let inQuotes = false;
        for (let i = 0; i < text.length; i++) {
            const c = text[i];
            if (c === '"') {
                if (inQuotes && text[i+1] === '"') {
                    cur += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (c === ',' && !inQuotes) {
                result.push(cur.trim());
                cur = '';
            } else {
                cur += c;
            }
        }
        result.push(cur.trim());
        return result;
    }

    function resetCsvUpload() {
        parsedCsvRowsCache = [];
        document.getElementById('csvFileInput').value = '';
        document.getElementById('csvDropZone').style.display = 'block';
        document.getElementById('csvPreviewSection').style.display = 'none';
    }

    async function submitCsvImport() {
        if (parsedCsvRowsCache.length === 0) return;

        const btn = document.getElementById('btnSubmitCsvImport');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Mengimport...';
        }

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/import-csv`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ csv_rows: parsedCsvRowsCache })
            });

            const result = await res.json();
            if (result.success && result.data) {
                const emptyRow = document.getElementById('emptyTestCasesRow');
                if (emptyRow) emptyRow.remove();

                result.data.forEach(tc => appendTestCaseRowToTable(tc));
                if (result.qg) updateQualityGateUI(result.qg);
                updateCounts();

                closeImportTemplateModal();
                resetCsvUpload();
                showToast(result.message || `${result.data.length} test case berhasil diimport`);
            } else {
                showToast(result.message || 'Gagal mengimport test case', 'error');
            }
        } catch (e) {
            console.error('Import CSV error:', e);
            showToast('Terjadi kesalahan saat mengimport data', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> <span>Import ke Report Ini</span>';
            }
        }
    }

    const PRESET_TEMPLATES = {
        'auth': [
            { scenario: 'Verifikasi login dengan kredensial valid', type: 'Positive', priority: 'High', steps: "1. Buka halaman login\n2. Masukkan email & password valid\n3. Klik Masuk", expected: 'Berhasil masuk ke dashboard utama' },
            { scenario: 'Verifikasi login dengan password salah', type: 'Negative', priority: 'High', steps: "1. Buka halaman login\n2. Masukkan password yang salah\n3. Klik Masuk", expected: 'Tampil pesan error kredensial tidak valid' },
            { scenario: 'Verifikasi validasi kolom wajib kosong pada form login', type: 'Negative', priority: 'Medium', steps: "1. Buka halaman login\n2. Kosongkan email dan password\n3. Klik Masuk", expected: 'Muncul pesan validasi wajib diisi' },
            { scenario: 'Verifikasi fungsi logout dan penghapusan sesi aktif', type: 'Positive', priority: 'High', steps: "1. Klik tombol Logout\n2. Tekan tombol Back pada browser", expected: 'Sesi berakhir dan diarahkan kembali ke halaman login' },
            { scenario: 'Verifikasi proteksi halaman tanpa hak akses yang sesuai', type: 'Security', priority: 'Critical', steps: "1. Login sebagai user standar\n2. Akses endpoint khusus admin", expected: 'Akses ditolak (403 Forbidden)' }
        ],
        'crud': [
            { scenario: 'Verifikasi tambah data baru dengan seluruh field valid', type: 'Positive', priority: 'High', steps: "1. Buka form tambah data\n2. Isi seluruh field wajib\n3. Klik Simpan", expected: 'Data berhasil disimpan dan tampil di daftar' },
            { scenario: 'Verifikasi pencegahan submit saat field wajib dikosongkan', type: 'Negative', priority: 'Medium', steps: "1. Buka form tambah data\n2. Kosongkan field utama\n3. Klik Simpan", expected: 'Tampil pesan validasi pada field yang kosong' },
            { scenario: 'Verifikasi tampilan rincian detail data', type: 'Positive', priority: 'Medium', steps: "1. Pilih salah satu baris data\n2. Buka halaman detail", expected: 'Seluruh atribut tampil sesuai dengan data yang disimpan' },
            { scenario: 'Verifikasi pembaruan (update) data yang sudah ada', type: 'Positive', priority: 'High', steps: "1. Buka form edit\n2. Ubah nilai field\n3. Klik Simpan Perubahan", expected: 'Perubahan tersimpan dan terbarui di tabel' },
            { scenario: 'Verifikasi hapus data beserta dialog konfirmasi', type: 'Positive', priority: 'High', steps: "1. Klik tombol Hapus\n2. Konfirmasi penghapusan", expected: 'Data terhapus dari daftar' }
        ],
        'upload': [
            { scenario: 'Verifikasi unggah file sesuai format dan batas ukuran', type: 'Positive', priority: 'High', steps: "1. Pilih file dokumen valid (< 2MB)\n2. Klik Upload", expected: 'File berhasil diunggah dan tersimpan' },
            { scenario: 'Verifikasi penolakan file yang melebihi ukuran maksimal', type: 'Negative', priority: 'High', steps: "1. Pilih file berukuran > 10MB\n2. Klik Upload", expected: 'Tampil peringatan ukuran file melebihi batas' },
            { scenario: 'Verifikasi pemblokiran ekstensi file berbahaya (.exe / .sh)', type: 'Security', priority: 'Critical', steps: "1. Pilih file berekstensi .exe\n2. Klik Upload", expected: 'Sistem menolak format file tersebut' },
            { scenario: 'Verifikasi unduh / export laporan ke format Excel', type: 'Positive', priority: 'Medium', steps: "1. Klik tombol Export Excel\n2. Buka file hasil unduhan", expected: 'File Excel berhasil diunduh dengan isi data yang akurat' }
        ],
        'smoke': [
            { scenario: 'Verifikasi halaman utama dan dashboard dapat dimuat tanpa error', type: 'Positive', priority: 'Critical', steps: "1. Buka halaman utama aplikasi\n2. Periksa tampilan dan console log", expected: 'Halaman tampil utuh tanpa error 500 atau exception' },
            { scenario: 'Verifikasi koneksi database dan endpoint utama merespon 200 OK', type: 'Positive', priority: 'Critical', steps: "1. Akses modul utama\n2. Muat daftar data", expected: 'Data berhasil dimuat dengan waktu respon normal' },
            { scenario: 'Verifikasi tampilan halaman 404 pada rute yang tidak ditemukan', type: 'Negative', priority: 'Low', steps: "1. Akses URL acak yang tidak terdaftar", expected: 'Menampilkan halaman 404 yang rapi' }
        ]
    };

    async function insertPresetTemplate(key) {
        const items = PRESET_TEMPLATES[key];
        if (!items) return;

        const existingCount = document.querySelectorAll('#testCaseTableBody tr.tc-row').length;
        parsedCsvRowsCache = items.map((item, idx) => ({
            tcid: `${PROJECT_CODE}-TC-${String(existingCount + idx + 1).padStart(3, '0')}`,
            scenario: item.scenario,
            type: item.type,
            priority: item.priority,
            steps: item.steps,
            expected: item.expected,
            status: 'Not Executed'
        }));

        await submitCsvImport();
    }

    // ──────────────────────────────────────────────────────
    // 10. QUICK BUG LINKING (TWO-WAY SYNC WITH MODAL)
    // ──────────────────────────────────────────────────────

    function openQuickBugForTcId(tcInternalId) {
        const row = document.getElementById('tcRow-' + tcInternalId);
        if (!row || !row.dataset.tc) return;
        const tc = JSON.parse(row.dataset.tc);
        const dyn = tc.dynamic_fields || {};

        let stepsText = '';
        let expectedText = '';
        if (tc.steps && tc.steps.length > 0) {
            tc.steps.forEach((s, i) => {
                const desc = s.step_description || s.step || '';
                const exp = s.expected_result || s.expected || '';
                if (desc) stepsText += `${i + 1}. ${desc}\n`;
                if (exp && !expectedText) expectedText = exp;
            });
        }

        if (typeof window.openQuickBugModal === 'function') {
            window.openQuickBugModal(tc.id, {
                tcInternalId: tc.id,
                projectId: tc.project_id || PROJECT_ID,
                reportId: REPORT_ID,
                testCaseId: tc.test_case_id,
                title: `Defect: ${tc.title}`,
                module: dyn.moduleFeature || '',
                actual: tc.actual_result || '',
                evidence: tc.evidence_url || '',
                stepsText: stepsText.trim(),
                expectedText: expectedText
            });
        }
    }

    function triggerQuickBugFromDrawer() {
        if (!currentOpenTc) return;
        openQuickBugForTcId(currentOpenTc.id);
    }

    window.onQuickBugCreated = async function(bugId, ctx) {
        if (!ctx || !ctx.tcInternalId) return;

        try {
            const res = await fetch(`/reports/${REPORT_ID}/test-cases/${ctx.tcInternalId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bug_ticket: bugId })
            });

            const result = await res.json();
            if (result.success) {
                updateTableRowVisuals(result.data);
                if (currentOpenTc && parseInt(currentOpenTc.id, 10) === parseInt(ctx.tcInternalId, 10)) {
                    document.getElementById('drawerBugTicket').value = bugId;
                }
                if (result.qg) updateQualityGateUI(result.qg);
                showToast(`Tiket Bug ${bugId} berhasil dibuat & ditautkan`);
            }
        } catch (e) {
            console.error('Error linking bug ticket to TC:', e);
        }
    };

    // ──────────────────────────────────────────────────────
    // 11. DOM BUILDER, FILTERS & QUALITY GATE UI UPDATER
    // ──────────────────────────────────────────────────────

    function appendTestCaseRowToTable(tc) {
        const tbody = document.getElementById('testCaseTableBody');
        const count = tbody.querySelectorAll('tr.tc-row').length + 1;
        const normSt = normalizeStatus(tc.status);
        const normPrio = ['Critical', 'High', 'Medium', 'Low'].includes(tc.priority) ? tc.priority : 'Medium';
        const isAiDraft = Boolean(tc.is_ai_generated && tc.review_status === 'needs_review');
        const dyn = tc.dynamic_fields || {};
        const stepCount = (tc.steps && tc.steps.length) ? tc.steps.length : 0;

        const tr = document.createElement('tr');
        tr.className = 'tc-row' + (isAiDraft ? ' is-ai-draft' : '');
        tr.id = 'tcRow-' + tc.id;
        tr.dataset.id = tc.id;
        tr.dataset.status = normSt;
        tr.dataset.aiReview = isAiDraft ? 'needs_review' : 'reviewed';
        tr.dataset.tc = JSON.stringify(tc);

        tr.innerHTML = `
            <td style="text-align: center;">
                <input type="checkbox" class="tc-checkbox tc-row-check" value="${tc.id}" onchange="onTcRowCheckChange()" />
            </td>
            <td>
                <button type="button" class="tc-id-btn" onclick="openDrawerForId(${tc.id})" title="Buka rincian test case">
                    ${escapeHtml(tc.test_case_id || ('TC-' + String(count).padStart(3, '0')))}
                </button>
            </td>
            <td>
                <div class="tc-scenario-wrap" onclick="openDrawerForId(${tc.id})">
                    <div class="tc-title-cell">${escapeHtml(tc.title)}</div>
                    <div class="tc-submeta-row">
                        ${isAiDraft ? `
                            <span class="ai-review-pill">
                                <i class="fas fa-magic"></i>
                                <span>Dibuat AI &bull; Perlu Review</span>
                                <button type="button" class="ai-inline-approve-btn" onclick="event.stopPropagation(); approveSingleAiTc(${tc.id})" title="Setujui draft AI ini">
                                    <i class="fas fa-check"></i> Setujui
                                </button>
                            </span>
                        ` : ''}
                        <span class="tc-meta-tag">${escapeHtml(dyn.caseType || 'Positive')}</span>
                        <span class="tc-step-count-tag">
                            <i class="fas fa-list-ol"></i>
                            <span class="step-num-text">${stepCount} langkah</span>
                        </span>
                    </div>
                </div>
            </td>
            <td>
                <select class="tc-prio-select prio-${normPrio.toLowerCase()}" onchange="onInlinePriorityChange(${tc.id}, this.value, this)">
                    <option value="Critical" ${normPrio === 'Critical' ? 'selected' : ''}>Critical</option>
                    <option value="High" ${normPrio === 'High' ? 'selected' : ''}>High</option>
                    <option value="Medium" ${normPrio === 'Medium' ? 'selected' : ''}>Medium</option>
                    <option value="Low" ${normPrio === 'Low' ? 'selected' : ''}>Low</option>
                </select>
            </td>
            <td>
                <select class="tc-status-select st-${normSt.toLowerCase().replace(/[\s\/]/g, '-')}" onchange="onInlineStatusChange(${tc.id}, this.value, this)">
                    <option value="Not Executed" ${normSt === 'Not Executed' ? 'selected' : ''}>Not Executed</option>
                    <option value="Pass" ${normSt === 'Pass' ? 'selected' : ''}>Pass</option>
                    <option value="Fail" ${normSt === 'Fail' ? 'selected' : ''}>Fail</option>
                    <option value="Blocked" ${normSt === 'Blocked' ? 'selected' : ''}>Blocked</option>
                    <option value="N/A" ${normSt === 'N/A' ? 'selected' : ''}>N/A</option>
                </select>
            </td>
            <td>
                <div class="tc-bug-cell">
                    ${tc.bug_ticket
                        ? `<span class="bug-ticket-pill" onclick="openDrawerForId(${tc.id})"><i class="fas fa-bug"></i> ${escapeHtml(tc.bug_ticket)}</span>`
                        : (normSt === 'Fail'
                            ? `<button type="button" class="btn-log-bug-inline" onclick="openQuickBugForTcId(${tc.id})"><i class="fas fa-plus"></i> Catat Bug</button>`
                            : '<span class="empty-dash">-</span>')
                    }
                </div>
            </td>
            <td style="text-align: right;">
                <div class="row-action-group">
                    <button type="button" class="row-act-btn primary" onclick="openDrawerForId(${tc.id})" title="Buka Detail & Langkah">
                        <i class="fas fa-sliders-h"></i>
                    </button>
                    <button type="button" class="row-act-btn" onclick="duplicateSingleTc(${tc.id})" title="Duplikat">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="row-act-btn danger" onclick="deleteSingleTc(${tc.id})" title="Hapus">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(tr);
        updateCounts();
    }

    function updateCounts() {
        const rows = document.querySelectorAll('#testCaseTableBody tr.tc-row');
        const count = rows.length;

        document.getElementById('tableHeaderCountBadge').textContent = count;
        document.getElementById('headerTcTotalBadge').textContent = `${count} Test Cases`;
        document.getElementById('filterCountAll').textContent = count;

        let pass = 0, fail = 0, blocked = 0, notRun = 0, aiReview = 0;
        rows.forEach(r => {
            const st = r.dataset.status;
            if (st === 'Pass') pass++;
            else if (st === 'Fail') fail++;
            else if (st === 'Blocked') blocked++;
            else if (st === 'Not Executed') notRun++;

            if (r.dataset.aiReview === 'needs_review') aiReview++;
        });

        document.getElementById('filterCountPass').textContent = pass;
        document.getElementById('filterCountFail').textContent = fail;
        document.getElementById('filterCountBlocked').textContent = blocked;
        document.getElementById('filterCountNotExecuted').textContent = notRun;
        document.getElementById('filterCountAiReview').textContent = aiReview;
    }

    function filterTableByStatus(status, btn) {
        document.querySelectorAll('.tc-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const rows = document.querySelectorAll('#testCaseTableBody tr.tc-row');
        rows.forEach(r => {
            if (status === 'all') {
                r.style.display = '';
            } else if (status === 'needs_review') {
                r.style.display = (r.dataset.aiReview === 'needs_review') ? '' : 'none';
            } else {
                r.style.display = (r.dataset.status === status) ? '' : 'none';
            }
        });
    }

    function searchTableTestCases(query) {
        const q = query.toLowerCase().trim();
        const clearBtn = document.getElementById('btnClearSearch');
        if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

        const rows = document.querySelectorAll('#testCaseTableBody tr.tc-row');
        rows.forEach(r => {
            const text = r.textContent.toLowerCase();
            r.style.display = text.includes(q) ? '' : 'none';
        });
    }

    function updateQualityGateUI(qg) {
        if (!qg) return;

        const passRateEl = document.getElementById('qgPassRateVal');
        const passRateBar = document.getElementById('qgPassRateBar');
        if (passRateEl) {
            passRateEl.textContent = `${qg.pass_rate}%`;
            passRateEl.className = 'kpi-value ' + ((qg.pass_rate >= 95) ? 'text-emerald' : ((qg.pass_rate >= 80) ? 'text-amber' : 'text-rose'));
        }
        if (passRateBar) {
            passRateBar.style.width = `${Math.min(100, Math.max(0, qg.pass_rate))}%`;
            passRateBar.className = 'kpi-progress-fill ' + ((qg.pass_rate >= 95) ? 'fill-emerald' : ((qg.pass_rate >= 80) ? 'fill-amber' : 'fill-rose'));
        }

        const progEl = document.getElementById('qgProgressVal');
        const progBar = document.getElementById('qgProgressBar');
        if (progEl) progEl.textContent = `${qg.progress}%`;
        if (progBar) progBar.style.width = `${Math.min(100, Math.max(0, qg.progress))}%`;

        const totalEl = document.getElementById('qgTotalCasesVal');
        if (totalEl) totalEl.textContent = qg.total_cases;

        const passEl = document.getElementById('qgPassCountVal');
        if (passEl) passEl.textContent = qg.pass_count;

        const execEl = document.getElementById('qgExecutedCountVal');
        if (execEl) execEl.textContent = qg.executed_count;

        const notRunEl = document.getElementById('qgNotRunCountVal');
        if (notRunEl) notRunEl.textContent = qg.not_run_count;

        const passDist = document.getElementById('qgPassDistVal');
        if (passDist) passDist.textContent = qg.pass_count;

        const failDist = document.getElementById('qgFailDistVal');
        if (failDist) failDist.textContent = qg.fail_count;

        const blockDist = document.getElementById('qgBlockedDistVal');
        if (blockDist) blockDist.textContent = qg.blocked_count;

        const failBugEl = document.getElementById('qgFailWithoutBugVal');
        const defectHint = document.getElementById('qgDefectHint');
        if (failBugEl) {
            failBugEl.textContent = qg.fail_without_bug;
            failBugEl.className = 'kpi-value ' + (qg.fail_without_bug > 0 ? 'text-rose' : 'text-emerald');
        }
        if (defectHint) {
            defectHint.className = 'kpi-foot-note ' + (qg.fail_without_bug > 0 ? 'warn' : 'ok');
            defectHint.textContent = qg.fail_without_bug > 0
                ? 'Wajib tautkan Bug ID pada status Fail'
                : 'Semua status Fail sudah memiliki Bug ID';
        }

        const badgeContainer = document.getElementById('qgVerdictBadgeContainer');
        if (badgeContainer) {
            let pillHtml = '';
            if (qg.verdict === 'GO') {
                pillHtml = `<span id="qgVerdictBadge" class="verdict-pill go"><i class="fas fa-check-circle"></i> GO (SIAP RILIS)</span>`;
            } else if (qg.verdict === 'CONDITIONAL') {
                pillHtml = `<span id="qgVerdictBadge" class="verdict-pill conditional"><i class="fas fa-exclamation-triangle"></i> CONDITIONAL</span>`;
            } else {
                pillHtml = `<span id="qgVerdictBadge" class="verdict-pill nogo"><i class="fas fa-times-circle"></i> NO GO (BELUM SIAP)</span>`;
            }
            badgeContainer.innerHTML = `<span class="verdict-label">Quality Gate (QA-STD-001)</span>${pillHtml}`;
        }

        updateCounts();
    }

    function normalizeStatus(st) {
        if (!st) return 'Not Executed';
        const u = String(st).toUpperCase().replace(/[\s_\/]/g, '');
        if (u === 'PASS') return 'Pass';
        if (u === 'FAIL' || u === 'FAILED') return 'Fail';
        if (u === 'BLOCKED') return 'Blocked';
        if (u === 'NA') return 'N/A';
        return 'Not Executed';
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
@endpush
