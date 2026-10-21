<!-- ===== QUICK BUG LOGGER MODAL (QA-TPL-001 Aturan 3) ===== -->
<div id="quickBugModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: white; border-radius: 20px; width: 100%; max-width: 650px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; animation: popIn 0.2s ease-out;">
        <!-- Header Modal -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="fas fa-bug"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1e293b;">Catat Tiket Bug (QA-TPL-001)</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Aturan 3: Status Fail wajib memiliki Bug ID yang terhubung</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickBugModal()" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer; padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body Form -->
        <div style="padding: 20px 24px; overflow-y: auto; flex: 1;">
            <input type="hidden" id="quickBugTestIdx" value="">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Related Test Case ID</label>
                    <input type="text" id="quickBugRelatedTc" readonly style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-family: monospace; font-weight: 700; background: #f1f5f9; color: #4338ca;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Modul / Fitur</label>
                    <input type="text" id="quickBugModule" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                    Judul Bug <span style="color: #ef4444;">*</span> <span style="font-size: 11px; font-weight: normal; color: #64748b;">([Di mana] + [Apa yang salah])</span>
                </label>
                <input type="text" id="quickBugTitle" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Severity (Dampak)</label>
                    <select id="quickBugSeverity" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: white;">
                        <option value="Critical">Critical (Crash / Data Rusak / Fatal)</option>
                        <option value="Major" selected>Major (Fitur Utama Gagal)</option>
                        <option value="Minor">Minor (Fitur Sekunder)</option>
                        <option value="Trivial">Trivial (Kosmetik / Typo)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Priority (Urgensi)</label>
                    <select id="quickBugPriority" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: white;">
                        <option value="P1 - Urgent">P1 - Urgent (Blokir Release)</option>
                        <option value="P2 - High" selected>P2 - High (Harus Fix)</option>
                        <option value="P3 - Medium">P3 - Medium (Next Release)</option>
                        <option value="P4 - Low">P4 - Low (Boleh Tunda)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Langkah Reproduksi (Steps to Reproduce)</label>
                <textarea id="quickBugSteps" rows="3" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Expected Result</label>
                    <textarea id="quickBugExpected" rows="2" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;"></textarea>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Actual Result</label>
                    <textarea id="quickBugActual" rows="2" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;"></textarea>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">Link Evidence</label>
                <input type="text" id="quickBugEvidence" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;" placeholder="https://drive.google.com/...">
            </div>
        </div>

        <!-- Footer Modal -->
        <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc;">
            <button type="button" onclick="closeQuickBugModal()" style="padding: 8px 16px; border: 1px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Batal
            </button>
            <button type="button" id="btnSubmitQuickBug" onclick="submitQuickBug()" style="padding: 8px 20px; background: #dc2626; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-check"></i> Simpan & Tautkan Bug ID
            </button>
        </div>
    </div>
</div>

<script>
    window._quickBugContext = null;

    // Open Quick Bug Logger Modal
    window.openQuickBugModal = function(testIdx, context = null) {
        const modal = document.getElementById('quickBugModal');
        if (!modal) return;

        window._quickBugContext = context;
        document.getElementById('quickBugTestIdx').value = testIdx ?? '';

        if (context) {
            document.getElementById('quickBugRelatedTc').value = context.testCaseId || 'TC-001';
            document.getElementById('quickBugTitle').value = context.title || '';
            document.getElementById('quickBugModule').value = context.module || '';
            document.getElementById('quickBugActual').value = context.actual || '';
            document.getElementById('quickBugEvidence').value = context.evidence || '';
            document.getElementById('quickBugSteps').value = context.stepsText || '';
            document.getElementById('quickBugExpected').value = context.expectedText || '';
        } else {
            // Legacy form fallback
            const tcIdEl = document.getElementById(`testCaseId_${testIdx}`);
            const tcId = tcIdEl?.value || `TC-${parseInt(testIdx) + 1}`;
            document.getElementById('quickBugRelatedTc').value = tcId;

            const title = document.getElementById(`testTitle_${testIdx}`)?.value || '';
            document.getElementById('quickBugTitle').value = title ? `Masalah pada: ${title}` : '';

            const module = document.getElementById(`moduleFeature_${testIdx}`)?.value || '';
            document.getElementById('quickBugModule').value = module;

            const actual = document.getElementById(`actualResult_${testIdx}`)?.value || '';
            document.getElementById('quickBugActual').value = actual;

            const evidence = document.getElementById(`evidenceUrl_${testIdx}`)?.value || '';
            document.getElementById('quickBugEvidence').value = evidence;

            const row = document.querySelector(`.test-case-row[data-test-idx="${testIdx}"]`);
            let stepsText = '';
            let expectedText = '';
            if (row) {
                const stepItems = row.querySelectorAll('.step-item-card');
                stepItems.forEach((st, idx) => {
                    const s = st.querySelector('.form-control')?.value || '';
                    const e = st.querySelectorAll('.form-control')[1]?.value || '';
                    if (s) stepsText += `${idx + 1}. ${s}\n`;
                    if (e && !expectedText) expectedText = e;
                });
            }
            document.getElementById('quickBugSteps').value = stepsText.trim();
            document.getElementById('quickBugExpected').value = expectedText;
        }

        modal.style.display = 'flex';
    };

    window.closeQuickBugModal = function() {
        const modal = document.getElementById('quickBugModal');
        if (modal) modal.style.display = 'none';
    };

    window.submitQuickBug = async function() {
        const btn = document.getElementById('btnSubmitQuickBug');
        const testIdx = document.getElementById('quickBugTestIdx').value;
        const title = document.getElementById('quickBugTitle').value.trim();

        if (!title) {
            alert('Judul bug wajib diisi!');
            return;
        }

        const ctx = window._quickBugContext;
        const projectSelect = document.getElementById('project_id');
        const projectId = ctx?.projectId || projectSelect?.value || null;

        const payload = {
            project_id: projectId,
            related_test_case_id: document.getElementById('quickBugRelatedTc').value,
            module_feature: document.getElementById('quickBugModule').value,
            title: title,
            severity: document.getElementById('quickBugSeverity').value,
            priority: document.getElementById('quickBugPriority').value,
            steps_to_reproduce: document.getElementById('quickBugSteps').value,
            expected_result: document.getElementById('quickBugExpected').value,
            actual_result: document.getElementById('quickBugActual').value,
            evidence_url: document.getElementById('quickBugEvidence').value,
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        try {
            const res = await fetch("{{ route('bugs.quick-store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (data.success && data.bug_id) {
                if (typeof window.onQuickBugCreated === 'function' && ctx) {
                    window.onQuickBugCreated(data.bug_id, ctx);
                } else {
                    const bugTicketInput = document.getElementById(`bugTicket_${testIdx}`);
                    if (bugTicketInput) {
                        bugTicketInput.value = data.bug_id;
                        bugTicketInput.style.borderColor = '#16a34a';
                        bugTicketInput.style.background = '#f0fdf4';
                        bugTicketInput.dispatchEvent(new Event('input'));
                    }
                    alert(`Berhasil! Tiket [${data.bug_id}] telah dicatat ke Bug List dan ditautkan ke Test Case ini.`);
                }
                closeQuickBugModal();
            } else {
                alert('Gagal mencatat bug: ' + (data.message || 'Terjadi kesalahan sistem.'));
            }
        } catch (err) {
            alert('Error saat mengirim data bug: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Simpan & Tautkan Bug ID';
        }
    };
</script>
