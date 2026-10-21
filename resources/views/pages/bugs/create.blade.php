@extends('layouts.app')

@section('title', 'Catat Bug Baru — QA-TPL-001')

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
            <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 6px 0 0;">Catat Defect / Bug Baru</h1>
            <p style="color: #64748b; font-size: 13px; margin: 2px 0 0;">Standar Formulir QA-TPL-001 Master Template v0.2</p>
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

    <form action="{{ route('bugs.store') }}" method="POST">
        @csrf
        <input type="hidden" name="report_id" value="{{ old('report_id', $prefill['report_id'] ?? '') }}">
        <input type="hidden" name="test_case_id" value="{{ old('test_case_id', $prefill['test_case_id'] ?? '') }}">

        <!-- 1. Identitas & Informasi Umum -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-info-circle text-blue-500"></i> 1. Identitas Bug & Project
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <!-- Project -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Project <span style="color: #ef4444;">*</span></label>
                    <select name="project_id" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        <option value="">-- Pilih Project --</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ (old('project_id', $prefill['project_id']) == $p->id) ? 'selected' : '' }}>
                                {{ $p->formatted_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Bug ID -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Bug ID <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="bug_id" value="{{ old('bug_id', $defaultBugId) }}" required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace; font-weight: 700; background: #f8fafc;">
                    <span style="font-size: 11px; color: #64748b;">Format otomatis: BUG-001, BUG-002, dst.</span>
                </div>

                <!-- Tanggal Ditemukan -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Tanggal Ditemukan <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="found_date" value="{{ old('found_date', date('Y-m-d')) }}" required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <!-- Modul / Fitur -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Modul / Fitur</label>
                    <input type="text" name="module_feature" value="{{ old('module_feature', $prefill['module_feature']) }}" placeholder="Contoh: Login Page, Docking - Charging"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <!-- Bug Type -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Bug Type <span style="color: #ef4444;">*</span></label>
                    <select name="bug_type" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::BUG_TYPES as $bt)
                            <option value="{{ $bt }}" {{ old('bug_type') == $bt ? 'selected' : '' }}>{{ $bt }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Related Test Case ID (Aturan 3) -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Related Test Case ID (Aturan 3) <i class="fas fa-link text-indigo-500"></i>
                    </label>
                    <input type="text" name="related_test_case_id" value="{{ old('related_test_case_id', $prefill['related_test_case_id']) }}" placeholder="Contoh: SPC-LOGIN-002, DRN-PWR-001"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace; font-weight: 700;">
                    <span style="font-size: 11px; color: #64748b;">Hubungan dua arah: test case fail akan terhubung ke bug ini.</span>
                </div>
            </div>

            <!-- Judul Bug -->
            <div style="margin-top: 16px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Judul Bug <span style="color: #ef4444;">*</span> <span style="font-size: 11.5px; font-weight: normal; color: #64748b;">(Aturan penulisan: [Di mana] + [Apa yang salah])</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $prefill['title']) }}" required placeholder="Contoh: Pesan error tidak muncul saat password salah"
                       style="width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;">
            </div>
        </div>

        <!-- 2. Klasifikasi & Lingkungan -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-layer-group text-purple-500"></i> 2. Klasifikasi, Dampak & Lingkungan
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <!-- Severity -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Severity (Dampak) <span style="color: #ef4444;">*</span></label>
                    <select name="severity" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::SEVERITIES as $key => $desc)
                            <option value="{{ $key }}" {{ old('severity', 'Major') == $key ? 'selected' : '' }}>{{ $key }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Priority (Urgensi) <span style="color: #ef4444;">*</span></label>
                    <select name="priority" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::PRIORITIES as $key => $desc)
                            <option value="{{ $key }}" {{ old('priority', 'P2 - High') == $key ? 'selected' : '' }}>{{ $key }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Environment -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Environment <span style="color: #ef4444;">*</span></label>
                    <select name="environment" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::ENVIRONMENTS as $env)
                            <option value="{{ $env }}" {{ old('environment', 'SIT') == $env ? 'selected' : '' }}>{{ $env }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Device / Browser / Unit ID -->
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Device / Browser / Unit ID</label>
                    <input type="text" name="device_environment" value="{{ old('device_environment') }}" placeholder="Contoh: Chrome 129 / Drone Unit ID 01"
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
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Langkah Reproduksi <span style="font-size: 11.5px; font-weight: normal; color: #64748b;">(Bernomor, satu aksi per langkah)</span>
                </label>
                <textarea name="steps_to_reproduce" rows="4" placeholder="1. Buka halaman login&#10;2. Masukkan email valid&#10;3. Masukkan password salah&#10;4. Klik tombol Sign In"
                          style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; line-height: 1.5;">{{ old('steps_to_reproduce', $prefill['steps_to_reproduce']) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Expected Result</label>
                    <textarea name="expected_result" rows="3" placeholder="Sistem menampilkan pesan 'Email atau password salah'..."
                              style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">{{ old('expected_result', $prefill['expected_result']) }}</textarea>
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Actual Result <span style="font-size: 11px; font-weight: normal; color: #64748b;">(Aturan 5: Tulis yang TERLIHAT)</span>
                    </label>
                    <textarea name="actual_result" rows="3" placeholder="Halaman diam saja, tidak ada pesan validasi atau notifikasi..."
                              style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">{{ old('actual_result', $prefill['actual_result']) }}</textarea>
                </div>
            </div>

            <!-- Evidence URL (Aturan 4) -->
            <div>
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Evidence URL / Link Bukti <i class="fab fa-google-drive text-blue-500"></i>
                </label>
                <input type="text" name="evidence_url" value="{{ old('evidence_url', $prefill['evidence_url']) }}" placeholder="https://drive.google.com/... atau path screenshot/video/log"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                <span style="font-size: 11px; color: #64748b;">Wajib untuk severity Critical & Major per panduan QA-TPL-001.</span>
            </div>
        </div>

        <!-- 4. Penugasan & Lifecycle Awal -->
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <h3 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-tag text-emerald-500"></i> 4. Penugasan & Status Awal
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Assigned To (Dev)</label>
                    <input type="text" name="assigned_to_dev" value="{{ old('assigned_to_dev') }}" placeholder="Nama developer penanggung jawab"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Status Awal <span style="color: #ef4444;">*</span></label>
                    <select name="status" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; background: white;">
                        @foreach(\App\Models\Bug::STATUSES as $st)
                            <option value="{{ $st }}" {{ old('status', 'Open') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Versi Build / Firmware (Saat Ditemukan)</label>
                    <input type="text" name="build_version_found" value="{{ old('build_version_found') }}" placeholder="Contoh: v1.0.4-rc2, FW-2.1.0"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
            <a href="{{ route('bugs.index') }}" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none;">
                Batal
            </a>
            <button type="submit" class="btn-gradient-primary" style="padding: 10px 24px; font-size: 14px;">
                <i class="fas fa-save mr-1"></i> Simpan ke Bug List
            </button>
        </div>
    </form>
</div>
@endsection
