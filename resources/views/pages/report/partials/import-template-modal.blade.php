<!-- Import CSV & Template Skenario Modal (Custom Fixed Overlay) -->
<div id="importTemplateModal" class="qa-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="importTemplateModalLabel" onclick="if(event.target === this) closeImportTemplateModal()">
    <div class="qa-modal-dialog qa-modal-lg">

        <!-- Modal Header -->
        <div class="qa-modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="qa-modal-icon blue">
                    <i class="fas fa-file-import" aria-hidden="true"></i>
                </div>
                <div>
                    <h3 id="importTemplateModalLabel" class="qa-modal-title">
                        Template & Import CSV Test Case
                    </h3>
                    <p class="qa-modal-subtitle">
                        Gunakan paket template berulang antar sprint atau unggah file CSV dari spreadsheet.
                    </p>
                </div>
            </div>
            <button type="button" class="qa-modal-close" onclick="closeImportTemplateModal()" title="Tutup (ESC)" aria-label="Tutup modal">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Tab Navigation -->
        <div class="qa-modal-tabs" role="tablist">
            <button type="button" role="tab" aria-selected="true" class="qa-modal-tab active" id="tabBtnTemplate" onclick="switchImportTab('template')">
                <i class="fas fa-layer-group" aria-hidden="true"></i>
                <span>Paket Template Sprint</span>
            </button>
            <button type="button" role="tab" aria-selected="false" class="qa-modal-tab" id="tabBtnCsv" onclick="switchImportTab('csv')">
                <i class="fas fa-file-csv" aria-hidden="true"></i>
                <span>Import File CSV</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="qa-modal-body">

            <!-- TAB 1: RECURRING SPRINT TEMPLATES (Default Open for Speed) -->
            <div id="tabContentTemplate">
                <p style="font-size: 13px; color: #475569; margin: 0 0 14px;">
                    Pilih paket skenario standar yang berulang di setiap sprint untuk langsung ditambahkan ke tabel report:
                </p>

                <div class="template-grid-2">
                    <!-- Template 1: Authentication -->
                    <div class="template-card">
                        <div class="template-card-top">
                            <span class="template-icon-badge indigo">
                                <i class="fas fa-user-lock" aria-hidden="true"></i>
                            </span>
                            <span class="qa-badge-soft blue">5 Test Cases</span>
                        </div>
                        <h4 class="template-card-title">Autentikasi & Manajemen Sesi</h4>
                        <p class="template-card-desc">
                            Login valid, password salah, validasi form kosong, logout & revokasi sesi, serta proteksi otorisasi role.
                        </p>
                        <button type="button" class="qa-btn-outline-blue full" onclick="insertPresetTemplate('auth')">
                            <i class="fas fa-plus" aria-hidden="true"></i> Tambahkan Paket Ini
                        </button>
                    </div>

                    <!-- Template 2: CRUD Standard -->
                    <div class="template-card">
                        <div class="template-card-top">
                            <span class="template-icon-badge emerald">
                                <i class="fas fa-database" aria-hidden="true"></i>
                            </span>
                            <span class="qa-badge-soft green">5 Test Cases</span>
                        </div>
                        <h4 class="template-card-title">CRUD & Validasi Form Standar</h4>
                        <p class="template-card-desc">
                            Tambah data valid, validasi kolom wajib kosong, tampil detail data, pembaruan data, dan hapus dengan konfirmasi.
                        </p>
                        <button type="button" class="qa-btn-outline-blue full" onclick="insertPresetTemplate('crud')">
                            <i class="fas fa-plus" aria-hidden="true"></i> Tambahkan Paket Ini
                        </button>
                    </div>

                    <!-- Template 3: Upload & Export -->
                    <div class="template-card">
                        <div class="template-card-top">
                            <span class="template-icon-badge amber">
                                <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                            </span>
                            <span class="qa-badge-soft amber">4 Test Cases</span>
                        </div>
                        <h4 class="template-card-title">Upload Dokumen & Export Data</h4>
                        <p class="template-card-desc">
                            Upload file sesuai batas, penolakan ukuran berlebih, blokir ekstensi berbahaya (.exe/.sh), dan unduh laporan Excel.
                        </p>
                        <button type="button" class="qa-btn-outline-blue full" onclick="insertPresetTemplate('upload')">
                            <i class="fas fa-plus" aria-hidden="true"></i> Tambahkan Paket Ini
                        </button>
                    </div>

                    <!-- Template 4: Smoke Test -->
                    <div class="template-card">
                        <div class="template-card-top">
                            <span class="template-icon-badge rose">
                                <i class="fas fa-heartbeat" aria-hidden="true"></i>
                            </span>
                            <span class="qa-badge-soft rose">3 Test Cases</span>
                        </div>
                        <h4 class="template-card-title">Smoke Test & Kesiapan Rilis</h4>
                        <p class="template-card-desc">
                            Halaman utama & dashboard bebas error, respon API healthcheck 200 OK, dan penanganan halaman 404.
                        </p>
                        <button type="button" class="qa-btn-outline-blue full" onclick="insertPresetTemplate('smoke')">
                            <i class="fas fa-plus" aria-hidden="true"></i> Tambahkan Paket Ini
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 2: IMPORT CSV -->
            <div id="tabContentCsv" style="display: none;">
                <!-- Template Download Callout -->
                <div class="csv-format-banner">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: #1e40af;">
                            Format Kolom CSV Standar
                        </div>
                        <div style="font-size: 12px; color: #3b82f6; margin-top: 2px;">
                            <code>TC ID, Scenario, Type, Priority, Precondition, Steps, Expected Result, Status</code>
                        </div>
                    </div>
                    <a href="{{ route('reports.test-cases.csv-template') }}" class="qa-btn-secondary sm" style="text-decoration: none;">
                        <i class="fas fa-download" aria-hidden="true"></i> Unduh Contoh CSV
                    </a>
                </div>

                <!-- Drop Zone -->
                <div id="csvDropZone" class="csv-dropzone" onclick="document.getElementById('csvFileInput').click()">
                    <input type="file" id="csvFileInput" accept=".csv,text/csv,text/plain" style="display: none;" onchange="handleCsvFileSelected(this.files)" />
                    <div class="csv-dropzone-icon">
                        <i class="fas fa-file-csv" aria-hidden="true"></i>
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                        Klik untuk memilih file CSV
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        Format .csv (UTF-8), otomatis dipetakan ke tabel test case
                    </div>
                </div>

                <!-- File Info & Preview Section -->
                <div id="csvPreviewSection" style="display: none; margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-check-circle" style="color: #16a34a;" aria-hidden="true"></i>
                            <span id="csvFileName">data.csv</span>
                            <span id="csvRowCountBadge" class="qa-badge-soft green">0 baris</span>
                        </div>
                        <button type="button" class="qa-btn-secondary sm" onclick="resetCsvUpload()">
                            Ganti File
                        </button>
                    </div>

                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead style="background: #f8fafc; color: #475569; position: sticky; top: 0;">
                                <tr>
                                    <th style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">TC ID</th>
                                    <th style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">Skenario</th>
                                    <th style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">Prioritas</th>
                                    <th style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0;">Langkah</th>
                                </tr>
                            </thead>
                            <tbody id="csvPreviewBody"></tbody>
                        </table>
                    </div>

                    <div class="qa-modal-footer-inline" style="margin-top: 16px;">
                        <button type="button" class="qa-btn-secondary" onclick="resetCsvUpload()">
                            Batal
                        </button>
                        <button type="button" id="btnSubmitCsvImport" class="qa-btn-primary blue" onclick="submitCsvImport()">
                            <i class="fas fa-check" aria-hidden="true"></i>
                            <span>Import ke Report Ini</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
