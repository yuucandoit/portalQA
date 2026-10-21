@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - Buat Report Testing')

@section('page_title', 'Buat Report Testing')
@section('page_subtitle', 'Tahap 1: Isi informasi inti report, lalu kelola test case di halaman detail')

@section('content')
    @php
        $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
        $latestReports = $latestReports ?? collect();
        $defaultProjectId = old('project_id', request('project_id', $projects->count() === 1 ? $projects->first()->id : ''));
        $selectedTestType = old('test_type', 'integration');

        $testTypes = [
            'functional'  => ['label' => 'Functional',  'sub' => 'Alur & fitur utama',    'icon' => 'fa-check-circle'],
            'integration' => ['label' => 'Integration', 'sub' => 'Antar modul / API',     'icon' => 'fa-project-diagram'],
            'regression'  => ['label' => 'Regression',  'sub' => 'Cek dampak perubahan',  'icon' => 'fa-history'],
            'negative'    => ['label' => 'Negative',    'sub' => 'Input salah / ekstrem', 'icon' => 'fa-exclamation-triangle'],
            'validation'  => ['label' => 'Validation',  'sub' => 'Aturan & format form',  'icon' => 'fa-clipboard-check'],
            'performance' => ['label' => 'Performance', 'sub' => 'Kecepatan & beban',     'icon' => 'fa-tachometer-alt'],
            'reliability' => ['label' => 'Reliability', 'sub' => 'Stabilitas jangka panjang', 'icon' => 'fa-shield-alt'],
            'stress'      => ['label' => 'Stress',      'sub' => 'Batas kapasitas sistem','icon' => 'fa-bolt'],
            'hardware'    => ['label' => 'Hardware',    'sub' => 'Perangkat & sensor',    'icon' => 'fa-microchip'],
        ];
    @endphp

    <div class="create-report-wrapper">
        <!-- Top Bar: Back Link & Step Indicator -->
        <div class="top-nav-row">
            <a href="{{ route('reports.index') }}" class="btn-back">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Kembali ke Daftar Report</span>
            </a>

            <div class="step-pills" aria-label="Tahapan pembuatan report">
                <div class="step-pill active" aria-current="step">
                    <span class="step-num">1</span>
                    <span>Info Report</span>
                </div>
                <i class="fas fa-chevron-right step-arrow" aria-hidden="true"></i>
                <div class="step-pill">
                    <span class="step-num">2</span>
                    <span>Buat Test Case (AI / CSV / Manual)</span>
                </div>
                <i class="fas fa-chevron-right step-arrow" aria-hidden="true"></i>
                <div class="step-pill">
                    <span class="step-num">3</span>
                    <span>Eksekusi & Export</span>
                </div>
            </div>
        </div>

        <!-- Validation Error Summary -->
        @if ($errors->any())
            <div class="error-banner" role="alert">
                <div class="error-banner-icon">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                </div>
                <div class="error-banner-content">
                    <div class="error-banner-title">Mohon periksa kembali isian berikut:</div>
                    <ul class="error-banner-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Main Form Card -->
        <div class="content-card qa-form-card">
            <form id="qaForm" action="{{ route('reports.store') }}" method="POST" novalidate>
                @csrf

                <div class="form-card-header">
                    <div>
                        <h2 class="form-card-title">Informasi Inti Report</h2>
                        <p class="form-card-subtitle">
                            Pilih project dan tipe testing. Nama report, sprint, dan siklus disesuaikan otomatis agar Anda bisa langsung lanjut membuat test case.
                        </p>
                    </div>
                    <span class="time-badge">
                        <i class="fas fa-stopwatch" aria-hidden="true"></i> ~30 detik
                    </span>
                </div>

                <!-- SECTION 1: PROJECT SELECTION -->
                <div class="form-section">
                    <div class="field-header">
                        <label for="project" class="field-label">
                            Project / Sistem <span class="req-star" aria-hidden="true">*</span>
                        </label>
                        <div class="field-meta">
                            <span id="projCodeBadge" class="code-badge" style="display: none;"></span>
                            <span id="projSyncNotice" class="sync-notice" style="display: none;">
                                <i class="fas fa-check" aria-hidden="true"></i> Sprint mengikuti histori project
                            </span>
                        </div>
                    </div>

                    @if ($projects->count() > 0 && $projects->count() <= 8)
                        <!-- Quick Project Pills for 1-Click Selection -->
                        <div class="quick-project-grid" role="group" aria-label="Pilih cepat project">
                            @foreach ($projects as $proj)
                                @php
                                    $lastRep = $latestReports->get($proj->id);
                                    $isSelectedProj = (string) $defaultProjectId === (string) $proj->id;
                                @endphp
                                <button type="button"
                                        class="project-card-btn {{ $isSelectedProj ? 'active' : '' }}"
                                        data-project-id="{{ $proj->id }}"
                                        onclick="selectProjectQuick('{{ $proj->id }}')"
                                        aria-pressed="{{ $isSelectedProj ? 'true' : 'false' }}">
                                    <div class="project-card-top">
                                        <span class="project-card-code">{{ $proj->project_code ?? 'QA' }}</span>
                                        @if ($proj->version)
                                            <span class="project-card-ver">v{{ ltrim($proj->version, 'vV') }}</span>
                                        @endif
                                    </div>
                                    <div class="project-card-name">{{ $proj->name }}</div>
                                    @if ($lastRep && $lastRep->sprint)
                                        <div class="project-card-meta">Terakhir: {{ $lastRep->sprint }}</div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <!-- Standard Accessible Select (Always synced, supports keyboard & large lists) -->
                    <div class="select-wrapper {{ $projects->count() > 0 && $projects->count() <= 8 ? 'select-compact' : '' }}">
                        <select id="project" name="project_id" required class="form-input @error('project_id') is-invalid @enderror">
                            <option value="">-- Pilih Project --</option>
                            @foreach ($projects as $proj)
                                @php
                                    $lastRep = $latestReports->get($proj->id);
                                @endphp
                                <option value="{{ $proj->id }}"
                                        data-code="{{ $proj->project_code ?? 'QA' }}"
                                        data-name="{{ $proj->name }}"
                                        data-last-sprint="{{ $lastRep->sprint ?? '' }}"
                                        data-last-cycle="{{ $lastRep->cycle ?? '' }}"
                                        data-last-type="{{ $lastRep->test_type ?? '' }}"
                                        {{ (string) $defaultProjectId === (string) $proj->id ? 'selected' : '' }}>
                                    {{ $proj->formatted_name }} {{ $proj->version ? '(' . $proj->version . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('project_id')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SECTION 2: TIPE TESTING -->
                <div class="form-section">
                    <div class="field-header">
                        <label for="testType" class="field-label">
                            Tipe Testing <span class="req-star" aria-hidden="true">*</span>
                        </label>
                        <span class="field-hint">Klik kartu untuk memilih cepat</span>
                    </div>

                    <!-- 1-Click Test Type Cards -->
                    <div class="test-type-grid" role="radiogroup" aria-label="Pilih tipe testing">
                        @foreach ($testTypes as $typeKey => $typeMeta)
                            @php
                                $isActiveType = $selectedTestType === $typeKey;
                            @endphp
                            <button type="button"
                                    class="type-card-btn {{ $isActiveType ? 'active' : '' }}"
                                    data-type="{{ $typeKey }}"
                                    onclick="selectTestType('{{ $typeKey }}')"
                                    aria-pressed="{{ $isActiveType ? 'true' : 'false' }}">
                                <div class="type-card-icon">
                                    <i class="fas {{ $typeMeta['icon'] }}" aria-hidden="true"></i>
                                </div>
                                <div class="type-card-body">
                                    <div class="type-card-title">{{ $typeMeta['label'] }}</div>
                                    <div class="type-card-sub">{{ $typeMeta['sub'] }}</div>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <!-- Synced Native Select for Form Submission -->
                    <select id="testType" name="test_type" required class="sr-only-select" tabindex="-1" aria-hidden="true">
                        @foreach ($testTypes as $typeKey => $typeMeta)
                            <option value="{{ $typeKey }}" {{ $selectedTestType === $typeKey ? 'selected' : '' }}>
                                {{ $typeMeta['label'] }} Test
                            </option>
                        @endforeach
                    </select>
                    @error('test_type')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SECTION 3: SPRINT, SIKLUS & TANGGAL REPORT -->
                <div class="form-grid-3">
                    <!-- Sprint -->
                    <div class="form-section">
                        <div class="field-header">
                            <label for="sprint" class="field-label">Sprint</label>
                            <span class="field-hint">Bisa diketik atau klik tombol</span>
                        </div>
                        <div class="stepper-input-group">
                            <button type="button" class="stepper-btn" onclick="stepTextNumber('sprint', -1, 'Sprint')" title="Kurangi nomor Sprint" aria-label="Kurangi nomor Sprint">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                            <input type="text" id="sprint" name="sprint"
                                   class="form-input stepper-input @error('sprint') is-invalid @enderror"
                                   value="{{ old('sprint', 'Sprint 1') }}"
                                   placeholder="Contoh: Sprint 1" />
                            <button type="button" class="stepper-btn" onclick="stepTextNumber('sprint', 1, 'Sprint')" title="Tambah nomor Sprint" aria-label="Tambah nomor Sprint">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="preset-chips" role="group" aria-label="Pilihan cepat Sprint">
                            @foreach (['Sprint 1', 'Sprint 2', 'Sprint 3', 'Sprint 4', 'Sprint 5', 'Hotfix'] as $spPreset)
                                <button type="button" class="preset-chip" data-target="sprint" data-value="{{ $spPreset }}"
                                        onclick="setFieldPreset('sprint', '{{ $spPreset }}')">
                                    {{ $spPreset }}
                                </button>
                            @endforeach
                        </div>
                        @error('sprint')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Siklus Eksekusi -->
                    <div class="form-section">
                        <div class="field-header">
                            <label for="cycle" class="field-label">Siklus Eksekusi</label>
                            <span class="field-hint">Iterasi pengujian</span>
                        </div>
                        <div class="stepper-input-group">
                            <button type="button" class="stepper-btn" onclick="stepTextNumber('cycle', -1, 'Siklus')" title="Kurangi nomor Siklus" aria-label="Kurangi nomor Siklus">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                            <input type="text" id="cycle" name="cycle"
                                   class="form-input stepper-input @error('cycle') is-invalid @enderror"
                                   value="{{ old('cycle', 'Siklus 1') }}"
                                   placeholder="Contoh: Siklus 1" />
                            <button type="button" class="stepper-btn" onclick="stepTextNumber('cycle', 1, 'Siklus')" title="Tambah nomor Siklus" aria-label="Tambah nomor Siklus">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="preset-chips" role="group" aria-label="Pilihan cepat Siklus">
                            @foreach (['Siklus 1', 'Siklus 2', 'Siklus 3', 'Retest', 'Final'] as $cyPreset)
                                <button type="button" class="preset-chip" data-target="cycle" data-value="{{ $cyPreset }}"
                                        onclick="setFieldPreset('cycle', '{{ $cyPreset }}')">
                                    {{ $cyPreset }}
                                </button>
                            @endforeach
                        </div>
                        @error('cycle')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tanggal Report -->
                    <div class="form-section">
                        <div class="field-header">
                            <label for="reportDate" class="field-label">
                                Tanggal Report <span class="req-star" aria-hidden="true">*</span>
                            </label>
                            <span class="field-hint">Default hari ini</span>
                        </div>
                        <input type="date" id="reportDate" name="report_date"
                               value="{{ old('report_date', date('Y-m-d')) }}" required
                               class="form-input @error('report_date') is-invalid @enderror" />
                        <div class="preset-chips" role="group" aria-label="Pilihan cepat Tanggal">
                            <button type="button" class="preset-chip date-chip" data-offset="0" onclick="setDateOffset(0)">
                                Hari Ini
                            </button>
                            <button type="button" class="preset-chip date-chip" data-offset="-1" onclick="setDateOffset(-1)">
                                Kemarin
                            </button>
                            <button type="button" class="preset-chip date-chip" data-offset="1" onclick="setDateOffset(1)">
                                Besok
                            </button>
                        </div>
                        @error('report_date')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- SECTION 4: NAMA REPORT (AUTO-GENERATED & EDITABLE) -->
                <div class="form-section report-name-box">
                    <div class="field-header">
                        <label for="reportName" class="field-label">
                            Nama Report <span class="req-star" aria-hidden="true">*</span>
                        </label>
                        <div class="report-name-controls">
                            <label class="include-cycle-toggle" title="Tambahkan nama siklus di akhir nama report">
                                <input type="checkbox" id="includeCycleCheck" onchange="autoGenerateReportName()" />
                                <span>Sertakan Siklus</span>
                            </label>
                            <span id="autoNameBadge" class="status-pill auto">
                                <i class="fas fa-sync-alt" aria-hidden="true"></i> Otomatis
                            </span>
                            <span id="manualNameBadge" class="status-pill manual" style="display: none;">
                                <i class="fas fa-pen" aria-hidden="true"></i> Diedit Manual
                            </span>
                            <button type="button" id="btnResetAutoName" class="btn-reset-auto" onclick="resetToAutoName()" style="display: none;">
                                <i class="fas fa-undo" aria-hidden="true"></i> Reset ke Otomatis
                            </button>
                        </div>
                    </div>

                    <div class="report-name-input-wrap">
                        <input type="text" id="reportName" name="report_name" required
                               class="form-input report-name-input @error('report_name') is-invalid @enderror"
                               value="{{ old('report_name') }}"
                               placeholder="Pilih project untuk membuat nama report otomatis..." />
                    </div>
                    <div class="field-helper-row">
                        <span>Format dasar: <code>[KodeProject]_[Tipe]_[Sprint]</code>. Klik langsung pada kolom di atas jika ingin mengubah nama.</span>
                    </div>
                    @error('report_name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SECTION 5: COLLAPSIBLE DESCRIPTION -->
                <div class="form-section desc-section">
                    <button type="button" id="btnToggleDesc" class="btn-toggle-desc"
                            onclick="toggleDescription()"
                            aria-expanded="{{ old('description') ? 'true' : 'false' }}"
                            aria-controls="descContainer">
                        <i id="descIcon" class="fas {{ old('description') ? 'fa-minus' : 'fa-plus' }}" aria-hidden="true"></i>
                        <span id="descLabel">{{ old('description') ? 'Sembunyikan deskripsi' : 'Tambah deskripsi' }}</span>
                        <span class="optional-tag">(opsional)</span>
                    </button>

                    <div id="descContainer" class="desc-container" style="display: {{ old('description') ? 'block' : 'none' }};">
                        <div class="desc-snippets">
                            <span class="snippet-label">Sisipkan template cepat:</span>
                            <button type="button" class="snippet-chip" onclick="insertDescSnippet('Ruang Lingkup:\n- ')">
                                + Ruang Lingkup
                            </button>
                            <button type="button" class="snippet-chip" onclick="insertDescSnippet('Environment & Build:\n- Server: Staging\n- Versi: ')">
                                + Environment
                            </button>
                            <button type="button" class="snippet-chip" onclick="insertDescSnippet('Catatan Pengujian:\n- ')">
                                + Catatan Pengujian
                            </button>
                        </div>
                        <textarea id="descriptionField" name="description" rows="3" class="form-input desc-textarea"
                                  placeholder="Tulis ruang lingkup fitur yang diuji, environment (Staging/Production), atau catatan khusus rilis ini...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- FOOTER ACTIONS -->
                <div class="form-footer">
                    <a href="{{ route('reports.index') }}" class="btn-cancel">
                        Batal
                    </a>

                    <div class="submit-group">
                        <span class="shortcut-hint" title="Tekan Ctrl+Enter atau Cmd+Enter untuk langsung menyimpan">
                            <kbd>Ctrl</kbd> + <kbd>Enter</kbd>
                        </span>
                        <button type="submit" id="submitBtn" class="btn-submit-primary">
                            <span id="submitBtnText">Buat Report</span>
                            <i id="submitBtnIcon" class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .create-report-wrapper {
            /* max-width: 920px; */
            margin: 0 auto;
        }

        /* Top Navigation & Stepper */
        .top-nav-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .btn-back {
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
            transition: background-color 0.15s, border-color 0.15s, color 0.15s;
        }

        .btn-back:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .step-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .step-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .step-pill.active {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
            font-weight: 700;
        }

        .step-num {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #cbd5e1;
            color: #1e293b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .step-pill.active .step-num {
            background: #2563eb;
            color: #ffffff;
        }

        .step-arrow {
            font-size: 10px;
            color: #94a3b8;
        }

        /* Error Banner */
        .error-banner {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: #991b1b;
        }

        .error-banner-icon {
            font-size: 18px;
            color: #dc2626;
            margin-top: 1px;
        }

        .error-banner-title {
            font-size: 13.5px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .error-banner-list {
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
        }

        /* Main Card */
        .qa-form-card {
            padding: 28px 32px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        .form-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 20px;
            margin-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
        }

        .form-card-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .form-card-subtitle {
            font-size: 13.5px;
            color: #475569;
            margin: 0;
            line-height: 1.5;
        }

        .time-badge {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Sections & Labels */
        .form-section {
            margin-bottom: 22px;
        }

        .field-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .field-label {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .req-star {
            color: #dc2626;
            margin-left: 2px;
        }

        .field-hint {
            font-size: 12px;
            color: #64748b;
        }

        .field-meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .code-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11.5px;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
        }

        .sync-notice {
            font-size: 12px;
            font-weight: 600;
            color: #15803d;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 2px 8px;
            border-radius: 6px;
        }

        /* Quick Project Cards */
        .quick-project-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(195px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }

        .project-card-btn {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            text-align: left;
            cursor: pointer;
            transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: 68px;
        }

        .project-card-btn:hover {
            border-color: #64748b;
            background: #f8fafc;
        }

        .project-card-btn.active {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        .project-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .project-card-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            font-weight: 700;
            color: #1d4ed8;
            background: #dbeafe;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .project-card-ver {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }

        .project-card-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }

        .project-card-meta {
            font-size: 11.5px;
            color: #475569;
        }

        .select-compact select {
            font-size: 13px;
            padding: 8px 12px;
            background-color: #f8fafc;
        }

        /* Test Type Grid */
        .test-type-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .type-card-btn {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
            cursor: pointer;
            transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
            min-height: 54px;
        }

        .type-card-btn:hover {
            border-color: #64748b;
            background: #f8fafc;
        }

        .type-card-btn.active {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        .type-card-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .type-card-btn.active .type-card-icon {
            background: #2563eb;
            color: #ffffff;
        }

        .type-card-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .type-card-sub {
            font-size: 11.5px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.2;
        }

        .sr-only-select {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        /* 3-Column Grid for Sprint, Cycle, Date */
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        /* Form Inputs & Steppers */
        .form-input {
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 9px;
            padding: 9px 12px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-input:hover {
            border-color: #94a3b8;
        }

        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .form-input.is-invalid {
            border-color: #dc2626;
        }

        .stepper-input-group {
            display: flex;
            align-items: stretch;
            border: 1.5px solid #cbd5e1;
            border-radius: 9px;
            overflow: hidden;
            background: #ffffff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .stepper-input-group:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .stepper-input {
            border: none;
            border-radius: 0;
            text-align: center;
            padding: 9px 8px;
            flex: 1;
            min-width: 0;
        }

        .stepper-input:focus {
            box-shadow: none;
        }

        .stepper-btn {
            background: #f8fafc;
            border: none;
            color: #334155;
            width: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.15s, color 0.15s;
        }

        .stepper-btn:first-child {
            border-right: 1px solid #e2e8f0;
        }

        .stepper-btn:last-child {
            border-left: 1px solid #e2e8f0;
        }

        .stepper-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Preset Chips */
        .preset-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .preset-chip {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 11.5px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .preset-chip:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .preset-chip.active {
            background: #dbeafe;
            border-color: #60a5fa;
            color: #1e40af;
            font-weight: 700;
        }

        /* Report Name Box */
        .report-name-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
        }

        .report-name-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .include-cycle-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            user-select: none;
        }

        .include-cycle-toggle input {
            accent-color: #2563eb;
            width: 14px;
            height: 14px;
            cursor: pointer;
        }

        .status-pill {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pill.auto {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-pill.manual {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-reset-auto {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-reset-auto:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .report-name-input {
            font-size: 15px;
            font-weight: 700;
            padding: 11px 14px;
            background: #ffffff;
        }

        .field-helper-row {
            font-size: 12px;
            color: #475569;
            margin-top: 6px;
        }

        .field-helper-row code {
            background: #e2e8f0;
            color: #0f172a;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 11.5px;
        }

        /* Collapsible Description */
        .desc-section {
            margin-bottom: 8px;
        }

        .btn-toggle-desc {
            background: #ffffff;
            border: 1px dashed #94a3b8;
            color: #334155;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.15s, border-color 0.15s;
        }

        .btn-toggle-desc:hover {
            background: #f8fafc;
            border-color: #475569;
            color: #0f172a;
        }

        .optional-tag {
            font-weight: 500;
            color: #64748b;
            font-size: 12px;
        }

        .desc-container {
            margin-top: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .desc-snippets {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .snippet-label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-right: 4px;
        }

        .snippet-chip {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 6px;
            cursor: pointer;
        }

        .snippet-chip:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .desc-textarea {
            font-weight: 500;
            line-height: 1.5;
            resize: vertical;
        }

        /* Field Error */
        .field-error {
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }

        /* Footer */
        .form-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-cancel {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 10px 20px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.15s, color 0.15s;
        }

        .btn-cancel:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .submit-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .shortcut-hint {
            font-size: 12px;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .shortcut-hint kbd {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            font-family: inherit;
        }

        .btn-submit-primary {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 11px 26px;
            border-radius: 9px;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.15s, transform 0.1s;
        }

        .btn-submit-primary:hover {
            background: #1d4ed8;
        }

        .btn-submit-primary:active {
            transform: translateY(1px);
        }

        .btn-submit-primary:disabled {
            background: #93c5fd;
            cursor: not-allowed;
        }

        /* Keyboard Focus Accessibility */
        .project-card-btn:focus-visible,
        .type-card-btn:focus-visible,
        .preset-chip:focus-visible,
        .stepper-btn:focus-visible,
        .btn-toggle-desc:focus-visible,
        .snippet-chip:focus-visible,
        .btn-reset-auto:focus-visible,
        .btn-back:focus-visible,
        .btn-cancel:focus-visible,
        .btn-submit-primary:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        /* Responsive Layout */
        @media (max-width: 768px) {
            .qa-form-card {
                padding: 20px 16px;
            }

            .test-type-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-grid-3 {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .shortcut-hint {
                display: none;
            }

            .form-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .submit-group,
            .btn-submit-primary,
            .btn-cancel {
                width: 100%;
                justify-content: center;
                text-align: center;
                min-height: 44px;
            }

            .stepper-btn {
                width: 44px;
                min-height: 44px;
            }

            .preset-chip {
                padding: 7px 12px;
                font-size: 12.5px;
            }
        }

        @media (max-width: 480px) {
            .test-type-grid {
                grid-template-columns: 1fr;
            }

            .step-pills .step-pill:not(.active) {
                display: none;
            }

            .step-arrow {
                display: none;
            }
        }
    </style>
@endpush

@push('scripts')
<script>
    let isManualReportName = {{ old('report_name') ? 'true' : 'false' }};
    let isSprintManuallyEdited = {{ old('sprint') ? 'true' : 'false' }};
    let isCycleManuallyEdited = {{ old('cycle') ? 'true' : 'false' }};

    const typeLabels = {
        'functional': 'Functional Report',
        'integration': 'Integration Report',
        'regression': 'Regression Report',
        'performance': 'Performance Report',
        'reliability': 'Reliability Report',
        'stress': 'Stress Report',
        'negative': 'Negative Report',
        'validation': 'Validation Report',
        'hardware': 'Hardware Report'
    };

    /**
     * Select a project from the 1-click project cards.
     */
    function selectProjectQuick(projectId) {
        const projSelect = document.getElementById('project');
        projSelect.value = projectId;
        projSelect.dispatchEvent(new Event('change'));
    }

    /**
     * Select a testing type from the 1-click type cards.
     */
    function selectTestType(typeKey) {
        const testTypeSelect = document.getElementById('testType');
        testTypeSelect.value = typeKey;

        document.querySelectorAll('.type-card-btn').forEach(btn => {
            const isMatch = btn.dataset.type === typeKey;
            btn.classList.toggle('active', isMatch);
            btn.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
        });

        autoGenerateReportName();
    }

    /**
     * Increment or decrement trailing number in Sprint / Cycle input.
     */
    function stepTextNumber(fieldId, delta, defaultPrefix) {
        const input = document.getElementById(fieldId);
        const currentVal = input.value.trim();
        const match = currentVal.match(/^(.*?)(\d+)\s*$/);

        if (match) {
            const prefix = match[1] || (defaultPrefix + ' ');
            const nextNum = Math.max(1, parseInt(match[2], 10) + delta);
            input.value = prefix + nextNum;
        } else {
            input.value = defaultPrefix + ' 1';
        }

        if (fieldId === 'sprint') isSprintManuallyEdited = true;
        if (fieldId === 'cycle') isCycleManuallyEdited = true;

        syncPresetChips();
        autoGenerateReportName();
    }

    /**
     * Set Sprint or Cycle value from a quick preset chip.
     */
    function setFieldPreset(fieldId, value) {
        const input = document.getElementById(fieldId);
        input.value = value;

        if (fieldId === 'sprint') isSprintManuallyEdited = true;
        if (fieldId === 'cycle') isCycleManuallyEdited = true;

        syncPresetChips();
        autoGenerateReportName();
    }

    /**
     * Set Report Date using day offset (0 = today, -1 = yesterday, 1 = tomorrow).
     */
    function setDateOffset(offsetDays) {
        const d = new Date();
        d.setDate(d.getDate() + offsetDays);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        const formatted = `${yyyy}-${mm}-${dd}`;

        const dateInput = document.getElementById('reportDate');
        dateInput.value = formatted;
        syncDateChips();
    }

    /**
     * Highlight active preset chips for Sprint and Cycle.
     */
    function syncPresetChips() {
        const sprintVal = document.getElementById('sprint').value.trim().toLowerCase();
        const cycleVal = document.getElementById('cycle').value.trim().toLowerCase();

        document.querySelectorAll('.preset-chip[data-target="sprint"]').forEach(chip => {
            chip.classList.toggle('active', chip.dataset.value.toLowerCase() === sprintVal);
        });

        document.querySelectorAll('.preset-chip[data-target="cycle"]').forEach(chip => {
            chip.classList.toggle('active', chip.dataset.value.toLowerCase() === cycleVal);
        });
    }

    /**
     * Highlight active date shortcut chip.
     */
    function syncDateChips() {
        const currentVal = document.getElementById('reportDate').value;
        document.querySelectorAll('.date-chip').forEach(chip => {
            const offset = parseInt(chip.dataset.offset, 10);
            const d = new Date();
            d.setDate(d.getDate() + offset);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            chip.classList.toggle('active', currentVal === `${yyyy}-${mm}-${dd}`);
        });
    }

    /**
     * Auto-generate Report Name from Project + Test Type + Sprint (+ optional Cycle).
     */
    function autoGenerateReportName() {
        const projSelect = document.getElementById('project');
        const selectedOpt = projSelect.options[projSelect.selectedIndex];
        const projCode = selectedOpt && selectedOpt.value ? (selectedOpt.dataset.code || selectedOpt.dataset.name) : '';
        const testType = document.getElementById('testType').value;
        const typeLabel = typeLabels[testType] || 'Testing Report';
        const sprint = document.getElementById('sprint').value.trim();
        const cycle = document.getElementById('cycle').value.trim();
        const includeCycle = document.getElementById('includeCycleCheck').checked;

        // Update project code badge
        const badge = document.getElementById('projCodeBadge');
        if (projCode) {
            badge.style.display = 'inline-block';
            badge.textContent = projCode;
        } else {
            badge.style.display = 'none';
        }

        // Sync project quick cards active state
        document.querySelectorAll('.project-card-btn').forEach(btn => {
            const isMatch = String(btn.dataset.projectId) === String(projSelect.value);
            btn.classList.toggle('active', isMatch);
            btn.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
        });

        if (isManualReportName) return;
        if (!projCode) return;

        let parts = [projCode, typeLabel];
        if (sprint) parts.push(sprint);
        if (includeCycle && cycle) parts.push(cycle);

        document.getElementById('reportName').value = parts.join('_');
        updateReportNameBadges(false);
    }

    function updateReportNameBadges(isManual) {
        document.getElementById('autoNameBadge').style.display = isManual ? 'none' : 'inline-flex';
        document.getElementById('manualNameBadge').style.display = isManual ? 'inline-flex' : 'none';
        document.getElementById('btnResetAutoName').style.display = isManual ? 'inline-flex' : 'none';
    }

    function resetToAutoName() {
        isManualReportName = false;
        autoGenerateReportName();
        updateReportNameBadges(false);
    }

    /**
     * Toggle collapsible description area.
     */
    function toggleDescription() {
        const container = document.getElementById('descContainer');
        const btn = document.getElementById('btnToggleDesc');
        const icon = document.getElementById('descIcon');
        const label = document.getElementById('descLabel');

        const isHidden = container.style.display === 'none' || container.style.display === '';
        if (isHidden) {
            container.style.display = 'block';
            btn.setAttribute('aria-expanded', 'true');
            icon.className = 'fas fa-minus';
            label.textContent = 'Sembunyikan deskripsi';
            document.getElementById('descriptionField').focus();
        } else {
            container.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
            icon.className = 'fas fa-plus';
            label.textContent = 'Tambah deskripsi';
        }
    }

    /**
     * Insert quick snippet into description textarea.
     */
    function insertDescSnippet(snippetText) {
        const textarea = document.getElementById('descriptionField');
        const current = textarea.value;
        textarea.value = current ? (current.trimEnd() + '\n\n' + snippetText) : snippetText;
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = textarea.value.length;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const projSelect = document.getElementById('project');
        const sprintInput = document.getElementById('sprint');
        const cycleInput = document.getElementById('cycle');
        const dateInput = document.getElementById('reportDate');
        const reportNameInput = document.getElementById('reportName');
        const qaForm = document.getElementById('qaForm');

        // When project changes, optionally sync Sprint & Cycle from project's latest report
        projSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            const syncNotice = document.getElementById('projSyncNotice');

            if (selectedOpt && selectedOpt.value) {
                const lastSprint = selectedOpt.dataset.lastSprint;
                const lastCycle = selectedOpt.dataset.lastCycle;
                let synced = false;

                if (lastSprint && !isSprintManuallyEdited) {
                    sprintInput.value = lastSprint;
                    synced = true;
                }
                if (lastCycle && !isCycleManuallyEdited) {
                    cycleInput.value = lastCycle;
                    synced = true;
                }

                syncNotice.style.display = synced ? 'inline-flex' : 'none';
                syncPresetChips();
            } else {
                syncNotice.style.display = 'none';
            }

            autoGenerateReportName();
        });

        sprintInput.addEventListener('input', function() {
            isSprintManuallyEdited = true;
            document.getElementById('projSyncNotice').style.display = 'none';
            syncPresetChips();
            autoGenerateReportName();
        });

        cycleInput.addEventListener('input', function() {
            isCycleManuallyEdited = true;
            syncPresetChips();
            autoGenerateReportName();
        });

        dateInput.addEventListener('change', syncDateChips);

        reportNameInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                isManualReportName = true;
                updateReportNameBadges(true);
            } else {
                isManualReportName = false;
                autoGenerateReportName();
            }
        });

        // Ctrl+Enter / Cmd+Enter shortcut to submit form quickly
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                qaForm.requestSubmit();
            }
        });

        // Prevent double submission & provide clear feedback
        qaForm.addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitBtnText');
            const submitIcon = document.getElementById('submitBtnIcon');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitText.textContent = 'Membuat Report...';
                submitIcon.className = 'fas fa-spinner fa-spin';
            }
        });

        // Initialize state on load
        syncPresetChips();
        syncDateChips();
        if (projSelect.value) {
            projSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
