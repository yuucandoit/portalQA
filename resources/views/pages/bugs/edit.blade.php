@extends('layouts.app')

@section('title', 'Edit Bug [' . $bug->bug_id . '] — QA-TPL-001')

@section('content')
<div class="content-wrapper" style="max-width: 1000px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <a href="{{ route('bugs.index') }}" style="color: #64748b; text-decoration: none; font-size: 13px;">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Bug List
                </a>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 6px;">
                <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0;">Edit Bug: {{ $bug->bug_id }}</h1>
                <span style="background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 6px; border: 1px solid #bfdbfe;">
                    Aging: {{ $bug->aging_days }} hari
                </span>
            </div>
            <p style="color: #64748b; font-size: 13px; margin: 2px 0 0;">Update lifecycle developer, retest QA, dan status defect</p>
        </div>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px;">
            <div style="font-weight: 700; margin-bottom: 6px;"><i class="fas fa-exclamation-circle mr-1"></i> Mohon periksa input berikut:</div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bugs.update', $bug->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- 1. Identitas Bug & Project -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-info-circle text-blue-500"></i> 1. Identitas Bug & Project
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Project <span style="color: #ef4444;">*</span></label>
                    <select name="project_id" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ (old('project_id', $bug->project_id) == $p->id) ? 'selected' : '' }}>
                                {{ $p->formatted_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Bug ID <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="bug_id" value="{{ old('bug_id', $bug->bug_id) }}" required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace; font-weight: 700; background: #f8fafc;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Tanggal Ditemukan <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="found_date" value="{{ old('found_date', $bug->found_date ? $bug->found_date->format('Y-m-d') : '') }}" required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Modul / Fitur</label>
                    <input type="text" name="module_feature" value="{{ old('module_feature', $bug->module_feature) }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Bug Type <span style="color: #ef4444;">*</span></label>
                    <select name="bug_type" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::BUG_TYPES as $bt)
                            <option value="{{ $bt }}" {{ old('bug_type', $bug->bug_type) == $bt ? 'selected' : '' }}>{{ $bt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Related Test Case ID (Aturan 3) <i class="fas fa-link text-indigo-500"></i>
                    </label>
                    <input type="text" name="related_test_case_id" value="{{ old('related_test_case_id', $bug->related_test_case_id) }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace; font-weight: 700;">
                </div>
            </div>

            <div style="margin-top: 16px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Judul Bug <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $bug->title) }}" required
                       style="width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;">
            </div>
        </div>

        <!-- 2. Klasifikasi & Lingkungan -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-layer-group text-purple-500"></i> 2. Klasifikasi, Dampak & Lingkungan
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Severity <span style="color: #ef4444;">*</span></label>
                    <select name="severity" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::SEVERITIES as $key => $desc)
                            <option value="{{ $key }}" {{ old('severity', $bug->severity) == $key ? 'selected' : '' }}>{{ $key }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Priority <span style="color: #ef4444;">*</span></label>
                    <select name="priority" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::PRIORITIES as $key => $desc)
                            <option value="{{ $key }}" {{ old('priority', $bug->priority) == $key ? 'selected' : '' }}>{{ $key }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Environment <span style="color: #ef4444;">*</span></label>
                    <select name="environment" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::ENVIRONMENTS as $env)
                            <option value="{{ $env }}" {{ old('environment', $bug->environment) == $env ? 'selected' : '' }}>{{ $env }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Device / Browser / Unit ID</label>
                    <input type="text" name="device_environment" value="{{ old('device_environment', $bug->device_environment) }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>
            </div>
        </div>

        <!-- 3. Detail Temuan & Langkah Reproduksi -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-list-ol text-amber-500"></i> 3. Langkah Reproduksi & Hasil
            </h3>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Langkah Reproduksi</label>
                <textarea name="steps_to_reproduce" rows="4"
                          style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; line-height: 1.5;">{{ old('steps_to_reproduce', $bug->steps_to_reproduce) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Expected Result</label>
                    <textarea name="expected_result" rows="3"
                              style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">{{ old('expected_result', $bug->expected_result) }}</textarea>
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Actual Result</label>
                    <textarea name="actual_result" rows="3"
                              style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">{{ old('actual_result', $bug->actual_result) }}</textarea>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Evidence URL / Link Bukti</label>
                <input type="text" name="evidence_url" value="{{ old('evidence_url', $bug->evidence_url) }}"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
            </div>
        </div>

        <!-- 4. Lifecycle & Status Terkini -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-sync-alt text-cyan-500"></i> 4. Status Defect & Penugasan
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Status Defect <span style="color: #ef4444;">*</span></label>
                    <select name="status" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white; font-weight: 700;">
                        @foreach(\App\Models\Bug::STATUSES as $st)
                            <option value="{{ $st }}" {{ old('status', $bug->status) == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Assigned To (Dev)</label>
                    <input type="text" name="assigned_to_dev" value="{{ old('assigned_to_dev', $bug->assigned_to_dev) }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Versi Build Ditemukan</label>
                    <input type="text" name="build_version_found" value="{{ old('build_version_found', $bug->build_version_found) }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>
            </div>
        </div>

        <!-- 5. Respons Developer (Kolom Dev per QA-TPL-001) -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #fed7aa; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #c2410c; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-code text-orange-500"></i> 5. Kolom Developer (Perbaikan / Fix)
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Tanggal Fix Dev</label>
                    <input type="date" name="fixed_date" value="{{ old('fixed_date', $bug->fixed_date ? $bug->fixed_date->format('Y-m-d') : '') }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Versi Build / Firmware (Fix)</label>
                    <input type="text" name="build_version_fix" value="{{ old('build_version_fix', $bug->build_version_fix) }}" placeholder="Contoh: v1.0.5, FW-2.1.1"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Catatan Dev (Penjelasan Solusi / Commit / PR)</label>
                <textarea name="dev_notes" rows="3" placeholder="Penyebab bug telah diperbaiki di commit #abc1234, handler validasi password telah ditambahkan..."
                          style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">{{ old('dev_notes', $bug->dev_notes) }}</textarea>
            </div>
        </div>

        <!-- 6. Retest QA (Kolom QA per QA-TPL-001) -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #bbf7d0; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #15803d; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-double text-green-500"></i> 6. Verifikasi & Retest QA
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Tanggal Retest</label>
                    <input type="date" name="retest_date" value="{{ old('retest_date', $bug->retest_date ? $bug->retest_date->format('Y-m-d') : '') }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Retest By (Nama QA)</label>
                    <input type="text" name="retested_by" value="{{ old('retested_by', $bug->retested_by ?: auth()->user()->name) }}"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Hasil Retest / Catatan QA</label>
                <textarea name="qa_retest_notes" rows="3" placeholder="Retest pada build v1.0.5 berhasil, pesan validasi muncul dengan benar. Status diubah ke Closed."
                          style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">{{ old('qa_retest_notes', $bug->qa_retest_notes) }}</textarea>
            </div>
        </div>

        <!-- Form Actions -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
            <a href="{{ route('bugs.index') }}" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none;">
                Batal
            </a>
            <button type="submit" class="btn-gradient-primary" style="padding: 10px 24px; font-size: 14px;">
                <i class="fas fa-save mr-1"></i> Update Bug List
            </button>
        </div>
    </form>
</div>
@endsection
