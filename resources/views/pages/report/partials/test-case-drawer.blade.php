<!-- Side Drawer Backdrop -->
<div id="drawerBackdrop" class="qa-drawer-backdrop" onclick="closeTestCaseDrawer()" style="display: none;"></div>

<!-- Test Case Side Drawer Panel -->
<aside id="tcSideDrawer" class="qa-side-drawer" aria-label="Detail Test Case">

    <!-- Drawer Header -->
    <div class="qa-drawer-header">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span id="drawerTcIdBadge" class="qa-tcid-badge">TC-001</span>
            <div class="drawer-nav-group" role="group" aria-label="Navigasi antar test case">
                <button type="button" id="btnDrawerPrev" class="drawer-icon-btn" onclick="navigateDrawer(-1)" title="Test Case Sebelumnya (Alt + ↑)">
                    <i class="fas fa-chevron-up" aria-hidden="true"></i>
                </button>
                <button type="button" id="btnDrawerNext" class="drawer-icon-btn" onclick="navigateDrawer(1)" title="Test Case Berikutnya (Alt + ↓)">
                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                </button>
            </div>
            <span id="drawerAutosaveStatus" class="drawer-autosave-pill">
                <i class="fas fa-check-circle" aria-hidden="true"></i> Tersimpan otomatis
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <button type="button" id="btnDrawerDuplicate" class="drawer-icon-btn" onclick="duplicateCurrentDrawerTc()" title="Duplikat Test Case Ini">
                <i class="fas fa-copy" aria-hidden="true"></i>
            </button>
            <button type="button" id="btnDrawerDelete" class="drawer-icon-btn danger" onclick="deleteCurrentDrawerTc()" title="Hapus Test Case Ini">
                <i class="fas fa-trash-alt" aria-hidden="true"></i>
            </button>
            <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 2px;"></div>
            <button type="button" class="drawer-icon-btn" onclick="closeTestCaseDrawer()" title="Tutup Panel (ESC)" aria-label="Tutup panel">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- AI Draft Review Banner -->
    <div id="drawerAiBanner" class="drawer-ai-banner" style="display: none;">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #86198f; font-weight: 600;">
            <i class="fas fa-magic" aria-hidden="true"></i>
            <span>Dibuat oleh AI. Periksa skenario & langkah sebelum disetujui.</span>
        </div>
        <button type="button" id="btnApproveAiDraft" class="btn-approve-ai-sm" onclick="approveCurrentDrawerTc()">
            <i class="fas fa-check" aria-hidden="true"></i> Setujui Draft
        </button>
    </div>

    <!-- Drawer Scrollable Body -->
    <div class="qa-drawer-body">
        <input type="hidden" id="drawerTcInternalId" />

        <!-- 1. Skenario / Judul Test Case -->
        <div class="drawer-section">
            <label for="drawerTitle" class="drawer-label">
                Skenario / Judul Test Case <span style="color: #dc2626;">*</span>
            </label>
            <textarea id="drawerTitle" rows="2" class="qa-input drawer-title-input"
                      placeholder="Contoh: Verifikasi checkout pesanan dengan metode pembayaran QRIS berhasil"
                      oninput="onDrawerFieldChange()"></textarea>
        </div>

        <!-- 2. Baris Status, Prioritas & Tipe Skenario -->
        <div class="drawer-meta-grid">
            <div>
                <label for="drawerStatus" class="drawer-sublabel">
                    Status Eksekusi <span style="color: #dc2626;">*</span>
                </label>
                <select id="drawerStatus" class="qa-input" style="font-weight: 700;" onchange="onDrawerFieldChange()">
                    <option value="Not Executed">Not Executed</option>
                    <option value="Pass">Pass</option>
                    <option value="Fail">Fail</option>
                    <option value="Blocked">Blocked</option>
                    <option value="N/A">N/A</option>
                </select>
            </div>

            <div>
                <label for="drawerPriority" class="drawer-sublabel">Prioritas</label>
                <select id="drawerPriority" class="qa-input" style="font-weight: 700;" onchange="onDrawerFieldChange()">
                    <option value="Critical">Critical</option>
                    <option value="High">High</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>

            <div>
                <label for="drawerType" class="drawer-sublabel">Tipe Skenario</label>
                <select id="drawerType" class="qa-input" style="font-weight: 600;" onchange="onDrawerFieldChange()">
                    <option value="Positive">Positive Case</option>
                    <option value="Negative">Negative Case</option>
                    <option value="Edge">Edge Case</option>
                    <option value="Security">Security Case</option>
                    <option value="Performance">Performance Case</option>
                </select>
            </div>
        </div>

        <!-- 3. Langkah Pengujian (Mini Table) -->
        <div class="drawer-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="drawer-label" style="margin: 0;">Langkah Pengujian</span>
                    <span id="drawerStepCountBadge" class="qa-badge-soft slate">0 langkah</span>
                </div>
                <button type="button" class="qa-btn-outline-blue sm" onclick="addDrawerStepRow()">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Langkah
                </button>
            </div>

            <div class="drawer-steps-box">
                <table class="drawer-steps-table">
                    <thead>
                        <tr>
                            <th style="width: 34px; text-align: center;">#</th>
                            <th>Langkah Uji (Step)</th>
                            <th>Ekspektasi (Expected)</th>
                            <th style="width: 135px;">Hasil Aktual</th>
                            <th style="width: 108px;">Status</th>
                            <th style="width: 30px;"></th>
                        </tr>
                    </thead>
                    <tbody id="drawerStepsTableBody">
                        <!-- Rendered dynamically via JS -->
                    </tbody>
                </table>
                <div id="drawerNoStepsHint" style="display: none; padding: 18px 12px; text-align: center; color: #64748b; font-size: 12.5px;">
                    Belum ada rincian langkah. Klik <strong>+ Tambah Langkah</strong> di kanan atas untuk menambahkan.
                </div>
            </div>
        </div>

        <!-- 4. Precondition, Modul & Requirement (Collapsible) -->
        <details id="drawerPrecondDetails" class="drawer-details-card">
            <summary class="drawer-details-summary">
                <span><i class="fas fa-layer-group" style="color: #2563eb; margin-right: 6px;" aria-hidden="true"></i> Precondition, Modul & Referensi Requirement</span>
                <span class="drawer-details-hint">Klik untuk buka/tutup</span>
            </summary>
            <div class="drawer-details-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label for="drawerModule" class="drawer-sublabel">Modul / Fitur</label>
                        <input type="text" id="drawerModule" class="qa-input" placeholder="Contoh: Checkout / Pembayaran" oninput="onDrawerFieldChange()" />
                    </div>
                    <div>
                        <label for="drawerRefReq" class="drawer-sublabel">Ref Requirement / User Story</label>
                        <input type="text" id="drawerRefReq" class="qa-input" placeholder="Contoh: US-102, REQ-04" oninput="onDrawerFieldChange()" />
                    </div>
                </div>
                <div>
                    <label for="drawerPrecondition" class="drawer-sublabel">Precondition (Kondisi Awal)</label>
                    <textarea id="drawerPrecondition" rows="2" class="qa-input" placeholder="Syarat awal sebelum skenario dijalankan (misal: User sudah login dan memiliki saldo aktif)..." oninput="onDrawerFieldChange()"></textarea>
                </div>
            </div>
        </details>

        <!-- 5. Hasil Aktual, Bukti Uji & Pelacakan Bug -->
        <div class="drawer-result-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <span class="drawer-label" style="margin: 0;">
                    <i class="fas fa-clipboard-check" style="color: #16a34a; margin-right: 5px;" aria-hidden="true"></i>
                    Hasil Eksekusi, Bukti & Defect
                </span>
                <button type="button" id="btnDrawerQuickBug" class="btn-quick-bug-sm" onclick="triggerQuickBugFromDrawer()" style="display: none;">
                    <i class="fas fa-bug" aria-hidden="true"></i> + Buat Tiket Bug
                </button>
            </div>

            <div style="margin-bottom: 12px;">
                <label for="drawerActualResult" class="drawer-sublabel">Hasil Aktual Keseluruhan</label>
                <textarea id="drawerActualResult" rows="2" class="qa-input"
                          placeholder="Catat hasil aktual pengujian (terutama jika status Fail atau Blocked)..."
                          oninput="onDrawerFieldChange()"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label for="drawerEvidenceUrl" class="drawer-sublabel">Link Bukti Uji (Evidence URL)</label>
                    <input type="url" id="drawerEvidenceUrl" class="qa-input"
                           placeholder="https://drive.google.com/..."
                           oninput="onDrawerFieldChange()" />
                </div>
                <div>
                    <label for="drawerBugTicket" class="drawer-sublabel">Tiket Bug Terkait (Bug ID)</label>
                    <input type="text" id="drawerBugTicket" class="qa-input"
                           placeholder="Contoh: BUG-001"
                           style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 700;"
                           oninput="onDrawerFieldChange()" />
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label for="drawerNotesQa" class="drawer-sublabel">Catatan QA</label>
                    <textarea id="drawerNotesQa" rows="2" class="qa-input" placeholder="Catatan tambahan QA..." oninput="onDrawerFieldChange()"></textarea>
                </div>
                <div>
                    <label for="drawerNotesProg" class="drawer-sublabel">Catatan Developer</label>
                    <textarea id="drawerNotesProg" rows="2" class="qa-input" placeholder="Catatan / respon developer..." oninput="onDrawerFieldChange()"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer Footer -->
    <div class="qa-drawer-footer">
        <span style="font-size: 12px; color: #64748b;">
            Perubahan disimpan otomatis saat Anda mengetik
        </span>
        <button type="button" class="qa-btn-primary dark" onclick="closeTestCaseDrawer()">
            Selesai & Tutup
        </button>
    </div>
</aside>
