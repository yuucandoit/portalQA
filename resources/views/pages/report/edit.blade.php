@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - Edit Test Report')

@section('page_title', 'Edit Lembar Kerja Testing QA')
@section('page_subtitle', 'Perbarui data test cases dan langkah pengujian dalam report ini')

@section('content')
    <!-- Top Back Navigation -->
    <div class="mb-4" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <a href="{{ route('reports.index') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Report
        </a>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" id="btnDriveGuide" onclick="openDriveFromForm()" style="background: white; color: #1e40af; border: 1.5px solid #bfdbfe; padding: 8px 16px; border-radius: 10px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s;" onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background='white'">
                <i class="fab fa-google-drive text-blue-600"></i> Struktur Google Drive (QA-WI-002)
            </button>
            @include('pages.report.partials.ai-modal')
        </div>
    </div>

    <div class="content-card qa-form-card">
        <form id="qaEditForm" action="{{ route('reports.update', $report->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- 1. General Metadata (Header Report) -->
            <div class="meta-section mb-6" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">

                    <!-- Report Name (full width) -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">
                            <i class="fas fa-file-signature text-blue-500 mr-1"></i> Nama Report
                        </label>
                        <input type="text" name="report_name" required class="form-control"
                               value="{{ $report->report_name }}" />
                    </div>

                    <!-- Project -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-folder text-blue-500 mr-1"></i> Project / System <span style="color:#ef4444;">*</span>
                        </label>
                        <select id="project" name="project_id" required class="form-control">
                            <option value="">Pilih Project...</option>
                            @foreach ($projects as $proj)
                                <option value="{{ $proj->id }}" {{ $report->project_id == $proj->id ? 'selected' : '' }}>{{ $proj->formatted_name }} ({{ $proj->version }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sprint & Cycle -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-running text-blue-500 mr-1"></i> Sprint
                        </label>
                        <input type="text" id="sprint" name="sprint" class="form-control"
                               value="{{ $report->sprint }}"
                               placeholder="e.g. Sprint 7" />
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-sync-alt text-blue-500 mr-1"></i> Siklus Eksekusi
                        </label>
                        <input type="text" id="cycle" name="cycle"
                               value="{{ $report->cycle ?? 'Siklus 1' }}" class="form-control"
                               placeholder="e.g. Siklus 1 / Siklus 2" />
                    </div>

                    <!-- Tipe Testing -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-vial text-blue-500 mr-1"></i> Tipe Testing <span style="color:#ef4444;">*</span>
                        </label>
                        <select id="testType" name="test_type" required class="form-control">
                            <option value="functional"  {{ $report->test_type === 'functional'  ? 'selected' : '' }}>Functional Test</option>
                            <option value="integration" {{ $report->test_type === 'integration' ? 'selected' : '' }}>Integration Test</option>
                            <option value="regression"  {{ $report->test_type === 'regression'  ? 'selected' : '' }}>Regression Test</option>
                            <option value="performance" {{ $report->test_type === 'performance' ? 'selected' : '' }}>Performance Test</option>
                            <option value="reliability" {{ $report->test_type === 'reliability' ? 'selected' : '' }}>Reliability Test</option>
                            <option value="stress"      {{ $report->test_type === 'stress'      ? 'selected' : '' }}>Stress Test</option>
                            <option value="negative"    {{ $report->test_type === 'negative'    ? 'selected' : '' }}>Negative Test</option>
                            <option value="validation"  {{ $report->test_type === 'validation'  ? 'selected' : '' }}>Validation Test</option>
                            <option value="hardware"    {{ $report->test_type === 'hardware'    ? 'selected' : '' }}>Hardware Test</option>
                        </select>
                    </div>

                    <!-- Report Date -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt text-blue-500 mr-1"></i> Report Date <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="date" id="reportDate" name="report_date"
                               value="{{ $report->report_date ? $report->report_date->format('Y-m-d') : date('Y-m-d') }}"
                               required class="form-control" />
                    </div>

                    <!-- Description -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">
                            <i class="fas fa-align-left text-blue-500 mr-1"></i> Deskripsi <span style="color:#94a3b8;font-weight:400;">(opsional)</span>
                        </label>
                        <textarea name="description" rows="1" class="form-control"
                                  placeholder="Deskripsi singkat tujuan report ini...">{{ $report->description }}</textarea>
                    </div>

                </div>
            </div>

            <!-- 2. Test Cases Container -->
            <div class="mb-6">
                <div class="test-cases-header mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-list-check text-blue-600 mr-2"></i>Daftar Test Cases
                        <span id="testCaseCount" class="count-badge">(0 test case)</span>
                    </h3>
                    <button type="button" id="addTestCaseBtn" class="btn-primary-add">
                        <i class="fas fa-plus"></i> Tambah Test Case
                    </button>
                </div>

                <div id="testCasesContainer" class="test-cases-list">
                    <!-- Test Case Items Will Be Rendered By JS -->
                </div>
            </div>

            <!-- 3. Form Actions -->
            <div class="form-actions pt-5 border-t border-slate-200">
                <a href="{{ route('reports.index') }}" class="btn-secondary">
                    Batal
                </a>
                <button type="submit" id="submitBtn" class="btn-gradient-submit">
                    <i class="fas fa-sync-alt mr-1"></i> Update Report QA
                </button>
            </div>
        </form>
    </div>

    @include('pages.report.partials.drive-modal')
    @include('pages.bugs.partials.quick-bug-modal')
    @include('pages.report.partials.hw-library-modal')
@endsection

@push('styles')
    <style>
        .qa-form-card {
            padding: 24px;
            border-radius: 16px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .meta-section {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 10px;
            margin: 10px;
        }

        .grid-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 13.5px;
            background: white;
            color: #1e293b;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3.5px rgba(102, 126, 234, 0.15) !important;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }

        .test-cases-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-title {
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            margin: 0;
        }

        .count-badge {
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            margin-left: 10px;
        }

        .btn-primary-add {
            background: #2563eb;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: all 0.2s ease;
        }

        .btn-primary-add:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .test-cases-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding-bottom: 10px;
        }

        /* Test Case Item Styling */
        .test-case-row {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }

        .test-case-row.expanded {
            border-left: 4px solid #667eea;
        }

        .test-case-header {
            padding: 14px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            gap: 12px;
            flex-wrap: wrap;
        }

        .test-case-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }

        .badge-status {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 11.5px;
            font-weight: 700;
        }

        .badge-pass { background: #dcfce7; color: #166534; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        .badge-blocked { background: #fef3c7; color: #92400e; }
        .badge-notrun { background: #e2e8f0; color: #475569; }
        .badge-na { background: #f1f5f9; color: #94a3b8; }

        .test-content {
            padding: 20px;
        }

        .dynamic-fields-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid #e2e8f0;
        }

        .fields-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .field-full {
            grid-column: span 2;
        }

        /* Steps Styling */
        .steps-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .step-item-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 14px;
            display: grid;
            grid-template-columns: 36px 1fr 1fr 40px;
            gap: 12px;
            align-items: start;
            transition: border 0.15s ease;
        }

        .step-item-card:hover {
            border-color: #cbd5e1;
        }

        .step-num-badge {
            font-weight: 700;
            color: #64748b;
            font-size: 13.5px;
            padding-top: 8px;
            text-align: center;
        }

        .btn-remove-step {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 16px;
            padding-top: 8px;
            display: flex;
            justify-content: center;
            transition: color 0.15s ease;
        }

        .btn-remove-step:hover {
            color: #b91c1c;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 10px;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-gradient-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-gradient-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(102, 126, 234, 0.4);
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */
        @media (max-width: 1024px) {
            .grid-meta {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .qa-form-card {
                padding: 16px;
            }

            .grid-meta {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .fields-grid {
                grid-template-columns: 1fr;
            }

            .field-full {
                grid-column: span 1;
            }

            .step-item-card {
                grid-template-columns: 1fr;
                gap: 10px;
                position: relative;
                padding-top: 36px;
            }

            .step-num-badge {
                position: absolute;
                top: 8px;
                left: 12px;
                padding-top: 0;
            }

            .btn-remove-step {
                position: absolute;
                top: 8px;
                right: 12px;
                padding-top: 0;
            }
        }

        @media (max-width: 480px) {
            .test-cases-header {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-primary-add {
                width: 100%;
                justify-content: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-secondary, .btn-gradient-submit {
                width: 100%;
            }

            .test-case-header-title {
                font-size: 13px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            'use strict';

            const fieldConfigs = {
                functional: {
                    title: 'Module / Fitur Spesifik (QA-TPL-001)',
                    fields: [
                        { id: 'moduleFeature', label: 'Modul / Menu', type: 'text', placeholder: 'e.g. Login Page, Overview' },
                        { id: 'feature', label: 'Fitur', type: 'text', placeholder: 'e.g. User Login, Campaign Engagement' },
                        { id: 'refRequirement', label: 'Ref Requirement', type: 'text', placeholder: 'e.g. SRS-01, PRD-3.2' },
                        { id: 'testData', label: 'Test Data (Nilai Input Uji)', type: 'textarea', placeholder: 'e.g. Username: [akun test]\nPassword: [password test]' }
                    ]
                },
                integration: {
                    title: 'Integration Point Spesifik',
                    fields: [
                        { id: 'integrationPoint', label: 'Integration Point / Module', type: 'text', placeholder: 'e.g. Order Service <-> Payment Gateway' },
                        { id: 'dependencies', label: 'Dependencies / Test Parameters', type: 'textarea', placeholder: 'e.g. Payment Gateway service aktif' },
                        { id: 'testData', label: 'Test Data', type: 'text', placeholder: 'e.g. Payload JSON transaksi' }
                    ]
                },
                regression: {
                    title: 'Related Feature Spesifik',
                    fields: [
                        { id: 'relatedFeature', label: 'Related Feature', type: 'text', placeholder: 'e.g. Payment Processing' },
                        { id: 'reasonLinked', label: 'Reason / Linked Test Case', type: 'textarea', placeholder: 'e.g. Bug fix in payment flow' },
                        { id: 'testData', label: 'Test Data', type: 'text', placeholder: 'e.g. Data akun lama vs baru' }
                    ]
                },
                performance: {
                    title: 'Test Parameters Performance',
                    fields: [
                        { id: 'testTypePerf', label: 'Test Type', type: 'select', options: ['Load Test', 'Stress Test', 'Spike Test', 'Endurance Test'] },
                        { id: 'testParams', label: 'Test Parameters', type: 'textarea', placeholder: 'e.g. Concurrent users: 100, Duration: 5 minutes' }
                    ]
                },
                reliability: {
                    title: 'Reliability Configuration',
                    fields: [
                        { id: 'testTypeRel', label: 'Test Type', type: 'select', options: ['Failure Rate', 'Mean Time Between Failures', 'Recovery Time'] },
                        { id: 'durationFailure', label: 'Duration / Failure Rate', type: 'text', placeholder: 'e.g. 2% failure rate over 24 hours' }
                    ]
                },
                stress: {
                    title: 'Stress Test Parameters',
                    fields: [
                        { id: 'testTypeStress', label: 'Test Type', type: 'select', options: ['Load Stress', 'Soak Stress', 'Spike Stress'] },
                        { id: 'stressParams', label: 'Test Parameters', type: 'textarea', placeholder: 'e.g. Max users: 500, Ramp-up: 10 min' }
                    ]
                },
                negative: {
                    title: 'Module / Fitur Negative Test',
                    fields: [
                        { id: 'moduleFeatureNeg', label: 'Module / Fitur', type: 'text', placeholder: 'e.g. Payment Gateway' },
                        { id: 'testDataNeg', label: 'Test Data', type: 'text', placeholder: 'e.g. Invalid card data, expired token' }
                    ]
                },
                validation: {
                    title: 'Spesifikasi & Kriteria Validasi',
                    fields: [
                        { id: 'validationType', label: 'Validation Type', type: 'select', options: ['Input Validation', 'Business Rule Validation', 'Boundary Value Analysis', 'Form Constraint', 'Authentication & Security Rule'] },
                        { id: 'validationCriteria', label: 'Kriteria / Aturan Validasi', type: 'textarea', placeholder: 'e.g. Password minimal 8 karakter, wajib angka dan huruf kapital' }
                    ]
                },
                hardware: {
                    title: 'Parameter Uji Hardware (QA-TPL-001)',
                    fields: [
                        { id: 'componentSubsystem', label: 'Komponen / Subsistem', type: 'text', placeholder: 'e.g. Docking – Charging, Drone – IMU' },
                        { id: 'parameterMeasured', label: 'Parameter Diukur *', type: 'hw_param_picker', placeholder: 'e.g. Suhu IMU maksimum, Tegangan baterai' },
                        { id: 'unit', label: 'Satuan', type: 'text', placeholder: 'e.g. °C, V, m, detik, siklus' },
                        { id: 'passLimitSpec', label: 'Batas Lulus (Spec) *', type: 'text', placeholder: 'e.g. <= 60 °C, 8/8 siklus (Aturan 7: Jangan kosong)' },
                        { id: 'specSource', label: 'Sumber Spec', type: 'text', placeholder: 'e.g. Datasheet, Konfirmasi Engineer' },
                        { id: 'measuredValue', label: 'Nilai Terukur (Observasi)', type: 'text', placeholder: 'e.g. 55,80 °C, 6/8' },
                        { id: 'deviceTested', label: 'Serial Number / Unit ID', type: 'text', placeholder: 'e.g. Drone Unit ID 01, SN: B902-11' },
                        { id: 'environmentSpec', label: 'Kondisi Lingkungan Saat Uji', type: 'text', placeholder: 'e.g. Suhu ambient 32°C, angin 3 m/s' }
                    ]
                }
            };

            const commonFields = [
                { id: 'testCaseId', label: 'TC ID (QA-TPL-001)', type: 'text', placeholder: 'e.g. SPC-LOGIN-001 atau DRN-PWR-001' },
                { id: 'testTitle', label: 'Scenario / Judul Test Case *', type: 'text', placeholder: 'e.g. Verifikasi Login Kredensial Valid / Sequence Charging' },
                { id: 'caseType', label: 'Tipe Test Case (QA-TPL-001)', type: 'select', options: ['Positive', 'Negative', 'Edge'] },
                { id: 'priority', label: 'Priority / Criticality', type: 'select', options: ['High', 'Medium', 'Low', 'Safety-Critical (HW)'] },
                { id: 'precondition', label: 'Precondition (Kondisi Awal / Device)', type: 'textarea', placeholder: 'Kondisi awal sebelum pengujian dilakukan...' }
            ];

            const resultFields = [
                { id: 'status', label: 'Status Pengujian *', type: 'select', options: ['Pass', 'Fail', 'Blocked', 'Not Run', 'N/A'] },
                { id: 'buckleupTicket', label: 'Tiket Eksekusi Buckleup', type: 'text', placeholder: 'e.g. NEWM2-255' },
                { id: 'bugTicket', label: 'Tiket Bug Buckleup (Wajib jika Fail)', type: 'text', placeholder: 'e.g. NEWM2-251' },
                { id: 'actualResult', label: 'Actual Result', type: 'textarea', placeholder: 'Tuliskan hasil akhir di lapangan...' },
                { id: 'notesQA', label: 'Notes QA', type: 'textarea', placeholder: 'Catatan tambahan analisa QA...' },
                { id: 'notesProgrammer', label: 'Notes Programmer', type: 'textarea', placeholder: 'Catatan/saran perbaikan untuk developer...' },
                { id: 'evidenceUrl', label: 'Evidence URL (Google Drive)', type: 'text', placeholder: 'https://drive.google.com/...' }
            ];

            const testCasesContainer = document.getElementById('testCasesContainer');
            const addTestCaseBtn = document.getElementById('addTestCaseBtn');

            function addStep(stepsContainer, testIdx, stepText = '', expectedText = '') {
                const steps = stepsContainer.querySelectorAll('.step-item-card');
                const stepNumber = steps.length + 1;

                const stepDiv = document.createElement('div');
                stepDiv.className = 'step-item-card';

                const colNum = document.createElement('div');
                colNum.className = 'step-num-badge';
                colNum.textContent = '#' + stepNumber;

                const colStep = document.createElement('div');
                const labelStep = document.createElement('label');
                labelStep.className = 'form-label';
                labelStep.style.fontSize = '12px';
                labelStep.style.color = '#64748b';
                labelStep.textContent = 'Test Steps / Checklist';
                const textareaStep = document.createElement('textarea');
                textareaStep.rows = 2;
                textareaStep.name = `test_cases[${testIdx}][steps][${stepNumber}][step]`;
                textareaStep.className = 'form-control';
                textareaStep.value = stepText || '';
                colStep.appendChild(labelStep);
                colStep.appendChild(textareaStep);

                const colExpected = document.createElement('div');
                const labelExpected = document.createElement('label');
                labelExpected.className = 'form-label';
                labelExpected.style.fontSize = '12px';
                labelExpected.style.color = '#64748b';
                labelExpected.textContent = 'Expected Result';
                const textareaExpected = document.createElement('textarea');
                textareaExpected.rows = 2;
                textareaExpected.name = `test_cases[${testIdx}][steps][${stepNumber}][expected]`;
                textareaExpected.className = 'form-control';
                textareaExpected.value = expectedText || '';
                colExpected.appendChild(labelExpected);
                colExpected.appendChild(textareaExpected);

                const colRemove = document.createElement('div');
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn-remove-step';
                removeBtn.title = 'Hapus Step';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const currentSteps = stepsContainer.querySelectorAll('.step-item-card');
                    if (currentSteps.length <= 1) {
                        alert('Minimal harus ada 1 langkah pengujian.');
                        return;
                    }
                    stepDiv.remove();
                    updateStepNumbers(stepsContainer);
                });
                colRemove.appendChild(removeBtn);

                stepDiv.appendChild(colNum);
                stepDiv.appendChild(colStep);
                stepDiv.appendChild(colExpected);
                stepDiv.appendChild(colRemove);

                stepsContainer.appendChild(stepDiv);
                updateStepNumbers(stepsContainer);
            }

            function updateStepNumbers(stepsContainer) {
                const steps = stepsContainer.querySelectorAll('.step-item-card');
                steps.forEach((step, index) => {
                    const numberDiv = step.querySelector('.step-num-badge');
                    if (numberDiv) {
                        numberDiv.textContent = '#' + (index + 1);
                    }
                });
            }

            function renderTestCase(testType, testIdx, data = null) {
                const config = fieldConfigs[testType];
                if (!config) return null;

                const row = document.createElement('div');
                row.className = 'test-case-row expanded';
                row.setAttribute('data-test-idx', testIdx);

                const header = document.createElement('div');
                header.className = 'test-case-header';

                const rawStatus = data?.fields?.status || 'Pass';
                const initialStatus = (rawStatus === 'PASS' ? 'Pass' : (rawStatus === 'FAILED' ? 'Fail' : (rawStatus === 'BLOCKED' ? 'Blocked' : rawStatus)));
                const initialBadgeClass = 
                    (initialStatus === 'Pass' || initialStatus === 'PASS') ? 'badge-pass' :
                    (initialStatus === 'Fail' || initialStatus === 'FAILED') ? 'badge-fail' :
                    (initialStatus === 'Blocked' || initialStatus === 'BLOCKED') ? 'badge-blocked' :
                    (initialStatus === 'Not Run') ? 'badge-notrun' : 'badge-na';

                header.innerHTML = `
                    <div class="test-case-header-title">
                        <span style="font-weight: 700; color: #334155;">#<span class="test-number">${testIdx + 1}</span></span>
                        <span style="font-size: 13.5px; color: #64748b;">Title: <strong class="test-title-display" style="color: #1e293b;">${data?.fields?.testTitle || '(No title)'}</strong></span>
                        <span class="badge-status ${initialBadgeClass}">${initialStatus}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="button" class="toggle-btn" style="background: none; border: none; color: #2563eb; cursor: pointer; font-size: 14px;">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <button type="button" class="btn-remove-row" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 14px; font-weight: bold;" onclick="removeTestCase(this.closest('.test-case-row'))">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                const content = document.createElement('div');
                content.className = 'test-content';

                const dynamicFields = document.createElement('div');
                dynamicFields.className = 'dynamic-fields-card';
                dynamicFields.setAttribute('data-test-idx', testIdx);

                let html = `
                    <div style="display: flex; flex-direction: column; gap: 18px;">
                        <h4 style="font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0;">${config.title}</h4>
                        <div class="fields-grid">
                `;

                commonFields.forEach(field => {
                    const value = data?.fields?.[field.id] || '';
                    html += `
                        <div class="${field.type === 'textarea' ? 'field-full' : ''}">
                            <label class="form-label">${field.label}</label>
                            ${field.type === 'textarea' ? 
                                `<textarea id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" rows="2" class="form-control" placeholder="${field.placeholder || ''}">${value}</textarea>` :
                                `<input type="text" id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" value="${value}" class="form-control" placeholder="${field.placeholder || ''}">`
                            }
                        </div>
                    `;
                });

                config.fields.forEach(field => {
                    const value = data?.fields?.[field.id] || '';
                    if (field.type === 'select') {
                        const options = field.options.map(opt => 
                            `<option value="${opt}" ${opt === value ? 'selected' : ''}>${opt}</option>`
                        ).join('');
                        html += `
                            <div>
                                <label class="form-label">${field.label}</label>
                                <select id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" class="form-control">
                                    ${options}
                                </select>
                            </div>
                        `;
                    } else if (field.type === 'hw_param_picker') {
                        html += `
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 4px;">
                                    <label class="form-label" style="margin-bottom: 0;">${field.label}</label>
                                    <button type="button" class="btn-pick-hw-lib" onclick="openHwLibraryModal(${testIdx})" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-book-open"></i> Library HW (QA-TPL-001)
                                    </button>
                                </div>
                                <input type="text" id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" value="${value}" class="form-control" placeholder="${field.placeholder || ''}">
                            </div>
                        `;
                    } else if (field.type === 'textarea') {
                        html += `
                            <div class="field-full">
                                <label class="form-label">${field.label}</label>
                                <textarea id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" rows="2" class="form-control" placeholder="${field.placeholder || ''}">${value}</textarea>
                            </div>
                        `;
                    } else {
                        html += `
                            <div>
                                <label class="form-label">${field.label}</label>
                                <input type="text" id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" value="${value}" class="form-control" placeholder="${field.placeholder || ''}">
                            </div>
                        `;
                    }
                });

                html += `
                        </div>
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 18px;">
                            <h4 style="font-size: 14.5px; font-weight: 700; color: #1e293b; margin-bottom: 14px;">Hasil Pengujian</h4>
                            <div class="fields-grid">
                `;

                resultFields.forEach(field => {
                    const value = data?.fields?.[field.id] || '';
                    if (field.type === 'select') {
                        const options = field.options.map(opt => 
                            `<option value="${opt}" ${opt === value || (opt === 'PASS' && !value) ? 'selected' : ''}>${opt}</option>`
                        ).join('');
                        html += `
                            <div>
                                <label class="form-label">${field.label}</label>
                                <select id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" class="form-control">
                                    ${options}
                                </select>
                            </div>
                        `;
                    } else if (field.id === 'bugTicket') {
                        html += `
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 4px;">
                                    <label class="form-label" style="margin-bottom: 0;">${field.label}</label>
                                    <button type="button" class="btn-quick-log-bug" onclick="openQuickBugModal(${testIdx})" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-plus-circle"></i> + Catat Bug Baru
                                    </button>
                                </div>
                                <input type="text" id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" value="${value}" class="form-control" placeholder="${field.placeholder || ''}" style="font-family: monospace; font-weight: 700;">
                                <div id="bugWarn_${testIdx}" style="display: none; font-size: 11px; color: #dc2626; margin-top: 4px; font-weight: 600;">
                                    <i class="fas fa-exclamation-triangle"></i> Status Fail WAJIB memiliki Bug ID (Aturan 3 QA-TPL-001)
                                </div>
                            </div>
                        `;
                    } else if (field.id === 'evidenceUrl') {
                        html += `
                            <div class="field-full">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                                    <label class="form-label" style="margin-bottom: 0;">${field.label}</label>
                                    <button type="button" class="btn-copy-evidence-fn" data-test-idx="${testIdx}" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-copy"></i> Salin Nama File Bukti Standar
                                    </button>
                                </div>
                                <input type="text" id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" value="${value}" class="form-control" placeholder="${field.placeholder || ''}">
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 5px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <i class="fab fa-google-drive text-blue-500"></i> Format Standar (QA-WI-002):
                                    <code id="evidenceFnPreview_${testIdx}" style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 700; color: #1e40af; font-size: 11px;">TC-${testIdx+1}_Test_Pass.png</code>
                                </div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="${field.type === 'textarea' ? 'field-full' : ''}">
                                <label class="form-label">${field.label}</label>
                                ${field.type === 'textarea' ? 
                                    `<textarea id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" rows="2" class="form-control" placeholder="${field.placeholder || ''}">${value}</textarea>` :
                                    `<input type="text" id="${field.id}_${testIdx}" name="test_cases[${testIdx}][${field.id}]" value="${value}" class="form-control" placeholder="${field.placeholder || ''}">`
                                }
                            </div>
                        `;
                    }
                });

                html += `
                            </div>
                        </div>
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 18px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                                <h4 style="font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0;">Langkah Pengujian (Test Steps) & Expected Result</h4>
                                <button type="button" class="add-step-btn" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-plus text-xs"></i> Tambah Step
                                </button>
                            </div>
                            <div class="steps-container steps-list">
                                <!-- Steps will be rendered by JS -->
                            </div>
                        </div>
                    </div>
                `;

                dynamicFields.innerHTML = html;
                content.appendChild(dynamicFields);
                row.appendChild(header);
                row.appendChild(content);

                const stepsContainer = dynamicFields.querySelector('.steps-list');
                const addStepBtn = dynamicFields.querySelector('.add-step-btn');

                if (data?.steps && data.steps.length > 0) {
                    data.steps.forEach(step => {
                        addStep(stepsContainer, testIdx, step.step, step.expected);
                    });
                } else {
                    addStep(stepsContainer, testIdx, 'Kirim transaksi dari Payment Gateway ke endpoint callback', 'Callback payment diterima dan status order terupdate');
                    addStep(stepsContainer, testIdx, 'Bandingkan data di Order Service dan Payment Gateway', 'Data transaksi konsisten di kedua sistem');
                }
                updateStepNumbers(stepsContainer);

                addStepBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    addStep(stepsContainer, testIdx, '', '');
                    updateStepNumbers(stepsContainer);
                });

                const toggleBtn = header.querySelector('.toggle-btn');
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const icon = this.querySelector('i');
                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                        icon.className = 'fas fa-chevron-down';
                    } else {
                        content.style.display = 'none';
                        icon.className = 'fas fa-chevron-up';
                    }
                });

                header.addEventListener('click', function(e) {
                    if (e.target.closest('.toggle-btn') || e.target.closest('.btn-remove-row')) return;
                    const icon = this.querySelector('.toggle-btn i');
                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                        icon.className = 'fas fa-chevron-down';
                    } else {
                        content.style.display = 'none';
                        icon.className = 'fas fa-chevron-up';
                    }
                });

                const titleInput = dynamicFields.querySelector(`#testTitle_${testIdx}`);
                const statusSelect = dynamicFields.querySelector(`#status_${testIdx}`);
                const titleDisplay = header.querySelector('.test-title-display');
                const statusBadge = header.querySelector('.badge-status');

                if (titleInput) {
                    titleInput.addEventListener('input', function() {
                        if (titleDisplay) {
                            titleDisplay.textContent = this.value || '(No title)';
                        }
                    });
                }

                function checkBugRequirement() {
                    const st = statusSelect?.value;
                    const bugInput = dynamicFields.querySelector(`#bugTicket_${testIdx}`);
                    const bugWarn = dynamicFields.querySelector(`#bugWarn_${testIdx}`);
                    if ((st === 'Fail' || st === 'FAILED') && bugInput && !bugInput.value.trim()) {
                        if (bugWarn) bugWarn.style.display = 'block';
                        bugInput.style.borderColor = '#ef4444';
                    } else if (bugInput) {
                        if (bugWarn) bugWarn.style.display = 'none';
                        bugInput.style.borderColor = '#cbd5e1';
                    }
                }

                const bugInputEl = dynamicFields.querySelector(`#bugTicket_${testIdx}`);
                if (bugInputEl) {
                    bugInputEl.addEventListener('input', checkBugRequirement);
                }

                if (statusSelect) {
                    statusSelect.addEventListener('change', function() {
                        // QA-TPL-001 Aturan 7: Hardware tanpa Batas Lulus tidak boleh Pass
                        const currentTestType = document.getElementById('testType')?.value;
                        if (currentTestType === 'hardware' && (this.value === 'Pass' || this.value === 'PASS')) {
                            const passLimitEl = dynamicFields.querySelector(`#passLimitSpec_${testIdx}`);
                            const limitVal = (passLimitEl?.value || '').trim().toLowerCase();
                            if (!limitVal || limitVal === 'tbd' || limitVal.includes('belum ada')) {
                                alert('⚠️ Aturan 7 (Hardware QA-TPL-001):\nParameter tanpa Batas Lulus Spec TIDAK BOLEH berstatus Pass.\nSelama spec belum tersedia, status diubah ke Blocked.');
                                this.value = 'Blocked';
                                const notesEl = dynamicFields.querySelector(`#notesQA_${testIdx}`);
                                if (notesEl && !notesEl.value.trim()) {
                                    notesEl.value = 'Spec belum tersedia';
                                }
                            }
                        }

                        checkBugRequirement();
                        if (statusBadge) {
                            statusBadge.textContent = this.value;
                            statusBadge.className = 'badge-status';
                            if (this.value === 'Pass' || this.value === 'PASS') {
                                statusBadge.classList.add('badge-pass');
                            } else if (this.value === 'Fail' || this.value === 'FAILED') {
                                statusBadge.classList.add('badge-fail');
                            } else if (this.value === 'Blocked' || this.value === 'BLOCKED') {
                                statusBadge.classList.add('badge-blocked');
                            } else if (this.value === 'Not Run') {
                                statusBadge.classList.add('badge-notrun');
                            } else {
                                statusBadge.classList.add('badge-na');
                            }
                        }
                    });
                }

                // Dynamic Evidence Filename Preview (QA-WI-002)
                function updateEvidencePreview() {
                    const tcId = dynamicFields.querySelector(`#testCaseId_${testIdx}`)?.value || `TC-${testIdx + 1}`;
                    const title = dynamicFields.querySelector(`#testTitle_${testIdx}`)?.value || 'Test';
                    const st = dynamicFields.querySelector(`#status_${testIdx}`)?.value || 'Pass';
                    const bug = dynamicFields.querySelector(`#bugTicket_${testIdx}`)?.value || '';
                    const date = document.getElementById('reportDate')?.value || new Date().toISOString().split('T')[0];
                    const fn = window.generateStandardFileName ? 
                        window.generateStandardFileName({ testCaseId: tcId, title: title, status: st, bugTicket: bug }, date) : 
                        `${tcId}_${title}_${st}_${date}.png`;
                    const previewEl = dynamicFields.querySelector(`#evidenceFnPreview_${testIdx}`);
                    if (previewEl) previewEl.textContent = fn;
                }

                ['testCaseId', 'testTitle', 'status', 'bugTicket'].forEach(fId => {
                    const el = dynamicFields.querySelector(`#${fId}_${testIdx}`);
                    if (el) {
                        el.addEventListener('input', updateEvidencePreview);
                        el.addEventListener('change', updateEvidencePreview);
                    }
                });
                setTimeout(updateEvidencePreview, 50);

                const copyEvidenceBtn = dynamicFields.querySelector('.btn-copy-evidence-fn');
                if (copyEvidenceBtn) {
                    copyEvidenceBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const previewEl = dynamicFields.querySelector(`#evidenceFnPreview_${testIdx}`);
                        if (previewEl && window.copyText) {
                            window.copyText(previewEl.textContent, 'Nama file bukti berhasil disalin!');
                        }
                    });
                }

                return row;
            }

            function addTestCase(data = null) {
                const testType = document.getElementById('testType').value;
                const testIdx = document.querySelectorAll('.test-case-row').length;
                
                const newRow = renderTestCase(testType, testIdx, data);
                if (newRow) {
                    testCasesContainer.appendChild(newRow);
                    updateTestNumbers();
                    updateTestCaseCount();
                    newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            window.addTestCase = addTestCase;

            window.removeTestCase = function(element) {
                const rows = document.querySelectorAll('.test-case-row');
                if (rows.length <= 1) {
                    alert('Minimal harus ada 1 test case.');
                    return;
                }
                element.remove();
                updateTestNumbers();
                updateTestCaseCount();
            };

            function updateTestNumbers() {
                const rows = document.querySelectorAll('.test-case-row');
                rows.forEach((row, index) => {
                    const numberSpan = row.querySelector('.test-number');
                    if (numberSpan) {
                        numberSpan.textContent = index + 1;
                    }
                });
            }
            window.updateTestNumbers = updateTestNumbers;

            function updateTestCaseCount() {
                const count = document.querySelectorAll('.test-case-row').length;
                document.getElementById('testCaseCount').textContent = `(${count} test case${count > 1 ? 's' : ''})`;
            }
            window.updateTestCaseCount = updateTestCaseCount;

            addTestCaseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                addTestCase();
            });

            document.getElementById('testType').addEventListener('change', function() {
                const testType = this.value;
                const rows = document.querySelectorAll('.test-case-row');
                const testCasesData = [];
                
                rows.forEach(row => {
                    const testIdx = parseInt(row.getAttribute('data-test-idx'));
                    const data = collectTestCaseData(row, testIdx);
                    testCasesData.push(data);
                });

                testCasesContainer.innerHTML = '';
                testCasesData.forEach((data, index) => {
                    const newRow = renderTestCase(testType, index, data);
                    if (newRow) {
                        testCasesContainer.appendChild(newRow);
                    }
                });
                updateTestNumbers();
                updateTestCaseCount();
            });

            function collectTestCaseData(row, testIdx) {
                const data = {
                    fields: {},
                    steps: []
                };

                const inputs = row.querySelectorAll('.dynamic-fields-card input, .dynamic-fields-card textarea, .dynamic-fields-card select');
                inputs.forEach(input => {
                    if (input.id) {
                        const cleanId = input.id.replace(`_${testIdx}`, '');
                        data.fields[cleanId] = input.value;
                    }
                });

                const steps = row.querySelectorAll('.step-item-card');
                steps.forEach(step => {
                    const stepText = step.querySelector('.step-input')?.value || '';
                    const expectedText = step.querySelector('.expected-input')?.value || '';
                    data.steps.push({ step: stepText, expected: expectedText });
                });

                return data;
            }

            // Open Google Drive Generator Modal with form state
            window.openDriveFromForm = function() {
                const projectSelect = document.getElementById('project_id');
                const projectCode = document.getElementById('projectCode')?.value || '';
                let projectName = 'PROJECT';
                if (projectSelect && projectSelect.selectedIndex >= 0) {
                    const selText = projectSelect.options[projectSelect.selectedIndex].text;
                    if (selText && selText !== '-- Pilih Project --') {
                        projectName = selText.replace(/^\s*\[.*?\]\s*/, '').trim();
                    }
                }
                const sprint = document.getElementById('sprint')?.value || '';
                const cycle = document.getElementById('cycle')?.value || 'Siklus 1';
                const reportName = document.querySelector('[name="report_name"]')?.value || 'Laporan Testing';
                const date = document.getElementById('reportDate')?.value || new Date().toISOString().split('T')[0];

                const rows = document.querySelectorAll('.test-case-row');
                const testCases = [];
                rows.forEach(row => {
                    const idx = row.getAttribute('data-test-idx');
                    const tcId = row.querySelector(`#testCaseId_${idx}`)?.value || '';
                    const title = row.querySelector(`#testTitle_${idx}`)?.value || '';
                    const status = row.querySelector(`#status_${idx}`)?.value || 'Pass';
                    const bugTicket = row.querySelector(`#bugTicket_${idx}`)?.value || '';
                    testCases.push({ testCaseId: tcId, title, status, bugTicket });
                });

                if (window.openDriveModal) {
                    window.openDriveModal({
                        projectCode,
                        projectName,
                        sprint,
                        cycle,
                        reportName,
                        date,
                        testCases
                    });
                }
            };

            // Prefill ALL test cases from existing DB report
            @foreach ($report->testCases as $tc)
            addTestCase({
                fields: {
                    testCaseId: @json($tc->test_case_id ?? ''),
                    testTitle: @json($tc->title),
                    priority: @json($tc->priority ?? 'Medium'),
                    precondition: @json($tc->precondition ?? ''),
                    status: @json($tc->status),
                    buckleupTicket: @json($tc->buckleup_ticket ?? ''),
                    bugTicket: @json($tc->bug_ticket ?? ''),
                    actualResult: @json($tc->actual_result ?? ''),
                    notesQA: @json($tc->notes_qa ?? ''),
                    notesProgrammer: @json($tc->notes_programmer ?? ''),
                    evidenceUrl: @json($tc->evidence_url ?? ''),
                    ...(@json((array)($tc->dynamic_fields ?? [])))
                },
                steps: {!! json_encode($tc->steps->map(fn($s) => ['step' => $s->step_description, 'expected' => $s->expected_result])->values()) !!}
            });
            @endforeach
            updateTestNumbers();
            updateTestCaseCount();

        })();
    </script>
@endpush
