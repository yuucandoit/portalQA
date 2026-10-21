<!-- AI Generate Test Cases Modal (Custom Fixed Overlay) -->
<div id="aiGenerateModal" class="qa-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="aiGenerateModalLabel" onclick="if(event.target === this) closeAiGenerateModal()">
    <div class="qa-modal-dialog qa-modal-xl">

        <!-- Modal Header -->
        <div class="qa-modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="qa-modal-icon indigo">
                    <i class="fas fa-magic" aria-hidden="true"></i>
                </div>
                <div>
                    <h3 id="aiGenerateModalLabel" class="qa-modal-title">
                        Generate Test Cases dengan AI
                    </h3>
                    <p class="qa-modal-subtitle">
                        Tempel User Story, Requirement, atau deskripsi fitur untuk membuat draft test case siap review.
                    </p>
                </div>
            </div>
            <button type="button" class="qa-modal-close" onclick="closeAiGenerateModal()" title="Tutup (ESC)" aria-label="Tutup modal">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="qa-modal-body">

            <!-- STEP 1: INPUT SECTION -->
            <div id="aiInputSection">
                <!-- Provider & Target Testing Type Bar -->
                <div class="ai-config-bar">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <label for="aiGenProvider" style="font-size: 12.5px; font-weight: 700; color: #334155; margin: 0;">
                            AI Provider:
                        </label>
                        <select id="aiGenProvider" class="qa-select-compact">
                            <option value="gemini" selected>Google Gemini (Recommended)</option>
                            <option value="openai">OpenAI (GPT-4o / mini)</option>
                            <option value="openrouter">OpenRouter</option>
                            <option value="ollama">Ollama (Local AI)</option>
                            <option value="omniroute">OmniRoute</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #475569;">
                        <span>Tipe Pengujian:</span>
                        <span class="qa-badge-soft blue">{{ $report->test_type_label }}</span>
                    </div>
                </div>

                <!-- User Story / Requirement Textarea -->
                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                        <label for="aiGenRawText" style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 0;">
                            User Story / Requirement / Deskripsi Fitur <span style="color: #dc2626;">*</span>
                        </label>
                        <span style="font-size: 12px; color: #64748b;">Mendukung format PRD, Acceptance Criteria, atau poin fitur</span>
                    </div>
                    <textarea id="aiGenRawText" rows="6" class="qa-textarea"
                              placeholder="Contoh:&#10;Sebagai pembeli, saya ingin memilih metode pembayaran QRIS saat checkout. Sistem menampilkan kode QR dinamis dengan batas waktu 15 menit. Jika pembayaran berhasil, status pesanan berubah menjadi 'Dibayar' dan stok berkurang. Jika melewati 15 menit tanpa pembayaran, transaksi otomatis kadaluarsa (expired)."></textarea>
                </div>

                <!-- Quick Example Prompt Chips -->
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span style="font-size: 12px; font-weight: 600; color: #64748b;">Contoh cepat:</span>
                        <button type="button" class="qa-chip-btn" onclick="fillAiExample('auth')">
                            Login & 2FA
                        </button>
                        <button type="button" class="qa-chip-btn" onclick="fillAiExample('checkout')">
                            Checkout & Voucher
                        </button>
                        <button type="button" class="qa-chip-btn" onclick="fillAiExample('upload')">
                            Upload Dokumen KYC
                        </button>
                    </div>
                    <button type="button" class="qa-chip-btn" onclick="document.getElementById('aiGenRawText').value=''; document.getElementById('aiGenRawText').focus();" style="color: #64748b;">
                        Bersihkan Teks
                    </button>
                </div>

                <!-- Footer Action -->
                <div class="qa-modal-footer-inline">
                    <button type="button" class="qa-btn-secondary" onclick="closeAiGenerateModal()">
                        Batal
                    </button>
                    <button type="button" id="btnRunAiGenerate" class="qa-btn-primary indigo" onclick="runAiGenerate()">
                        <i class="fas fa-magic" aria-hidden="true"></i>
                        <span>Buat Draft Test Case</span>
                    </button>
                </div>
            </div>

            <!-- STEP 1.5: LOADING STATE -->
            <div id="aiLoadingSection" style="display: none; padding: 56px 20px; text-align: center;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #eef2ff; color: #4f46e5; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 14px;">
                    <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                </div>
                <h4 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px;">
                    AI sedang menyusun skenario pengujian...
                </h4>
                <p style="font-size: 13px; color: #64748b; margin: 0;">
                    Menganalisis requirement untuk menghasilkan skenario positif, negatif, dan langkah pengujian.
                </p>
            </div>

            <!-- STEP 2: PREVIEW & REVIEW DRAFTS -->
            <div id="aiPreviewSection" style="display: none;">
                <!-- QA Responsibility Callout -->
                <div class="ai-review-callout">
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <i class="fas fa-user-check" style="color: #b45309; font-size: 15px; margin-top: 2px;" aria-hidden="true"></i>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #92400e;">
                                Draft hasil AI perlu direview oleh QA
                            </div>
                            <div style="font-size: 12px; color: #b45309; margin-top: 2px;">
                                Anda dapat langsung mengedit judul skenario di bawah ini sebelum menyimpannya ke dalam report.
                            </div>
                        </div>
                    </div>
                    <button type="button" class="qa-btn-secondary sm" onclick="backToAiInput()">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Ubah Requirement
                    </button>
                </div>

                <!-- Selection Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div style="font-size: 13.5px; font-weight: 700; color: #0f172a;">
                        Hasil Generate: <span id="aiDraftCountBadge" style="color: #4f46e5;">0</span> Test Case
                    </div>
                    <label style="font-size: 12.5px; font-weight: 600; color: #334155; margin: 0; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <input type="checkbox" id="aiSelectAllCheck" checked onchange="toggleAllAiDraftChecks(this.checked)" style="accent-color: #2563eb; width: 15px; height: 15px;" />
                        <span>Pilih Semua</span>
                    </label>
                </div>

                <!-- Draft Cards List -->
                <div id="aiDraftListContainer" style="display: flex; flex-direction: column; gap: 10px; max-height: 360px; overflow-y: auto; padding-right: 4px; margin-bottom: 18px;">
                    <!-- Rendered dynamically via JS -->
                </div>

                <!-- Bottom Save Buttons -->
                <div class="qa-modal-footer-inline">
                    <button type="button" class="qa-btn-secondary" onclick="backToAiInput()">
                        Kembali
                    </button>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" id="btnSaveAiAsDraft" class="qa-btn-outline-blue" onclick="saveSelectedAiDrafts(false)">
                            <i class="fas fa-tag" aria-hidden="true"></i>
                            <span>Simpan sebagai Draft (Perlu Review)</span>
                        </button>
                        <button type="button" id="btnSaveAiApproved" class="qa-btn-primary green" onclick="saveSelectedAiDrafts(true)">
                            <i class="fas fa-check-double" aria-hidden="true"></i>
                            <span>Setujui & Tambahkan ke Report</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
