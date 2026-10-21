<!-- Google Drive Folder Structure & Evidence Naming Helper (QA-WI-002) Modal -->
<div id="driveStructureModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; overflow-y: auto; padding: 20px 14px;">
    <div class="modal-dialog" style="max-width: 860px; margin: 30px auto; background: #ffffff; border-radius: 18px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0; animation: modalFadeIn 0.25s ease-out;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); display: flex; align-items: center; justify-content: center; color: #60a5fa; font-size: 20px;">
                    <i class="fab fa-google-drive"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                        Struktur Google Drive & Bukti Pengujian
                        <span style="font-size: 11px; font-weight: 700; background: rgba(59, 130, 246, 0.2); color: #93c5fd; padding: 2px 8px; border-radius: 6px; letter-spacing: 0.05em;">QA-WI-002</span>
                    </h3>
                    <p style="margin: 2px 0 0; font-size: 12.5px; color: #94a3b8;">
                        Panduan hierarki folder pengujian dan standardisasi format nama file bukti
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeDriveModal()" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; border-radius: 10px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 24px; max-height: calc(85vh - 120px); overflow-y: auto;">
            
            <!-- Context Banner -->
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Kode & Nama Proyek</span>
                    <div id="driveModalProjectName" style="font-size: 13.5px; font-weight: 700; color: #1e40af; margin-top: 2px;">—</div>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Sprint & Siklus</span>
                    <div id="driveModalSprintCycle" style="font-size: 13.5px; font-weight: 700; color: #1e40af; margin-top: 2px;">—</div>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Test Cases</span>
                    <div id="driveModalCaseCount" style="font-size: 13.5px; font-weight: 700; color: #1e40af; margin-top: 2px;">0 Kasus Uji</div>
                </div>
            </div>

            <!-- Tabs: Visual Folder Tree & Evidence Filenames -->
            <div style="display: flex; gap: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px;">
                <button type="button" id="tabBtnTree" onclick="switchDriveTab('tree')" style="background: none; border: none; padding: 10px 18px; font-size: 13.5px; font-weight: 700; color: #2563eb; border-bottom: 2px solid #2563eb; margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-folder-tree"></i> 1. Pohon Folder Google Drive
                </button>
                <button type="button" id="tabBtnList" onclick="switchDriveTab('list')" style="background: none; border: none; padding: 10px 18px; font-size: 13.5px; font-weight: 600; color: #64748b; border-bottom: 2px solid transparent; margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-image"></i> 2. Generator Nama File Bukti (Evidence)
                </button>
            </div>

            <!-- Tab 1: Folder Tree -->
            <div id="tabContentTree">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <span style="font-size: 12.5px; font-weight: 600; color: #475569;">
                        Hierarki Folder Sesuai SOP QA-WI-002:
                    </span>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" onclick="copyDriveFolderPath()" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;" title="Salin path hierarki folder">
                            <i class="fas fa-copy"></i> Salin Path Folder
                        </button>
                        <button type="button" onclick="copyMkdirCommand()" style="background: #1e293b; color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;" title="Salin perintah bash/terminal untuk buat folder lokal otomatis">
                            <i class="fas fa-terminal"></i> Salin Perintah `mkdir`
                        </button>
                    </div>
                </div>

                <!-- Tree View Visual Card -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 20px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; color: #1e293b; line-height: 1.8;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-folder text-blue-500" style="font-size: 16px;"></i>
                        <span id="treeRootFolder" style="font-weight: 700; color: #1e40af;">[KODE]_[NAMA_PROYEK]</span>
                        <span style="font-size: 11px; background: #dbeafe; color: #1e40af; padding: 1px 6px; border-radius: 4px; font-weight: 600;">Root Google Drive</span>
                    </div>

                    <div style="margin-left: 24px; padding-left: 14px; border-left: 2px dashed #cbd5e1; margin-top: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-folder text-indigo-500" style="font-size: 15px;"></i>
                            <span id="treeSprintCycleFolder" style="font-weight: 700; color: #4338ca;">Sprint 7 - Siklus 1</span>
                            <span style="font-size: 11px; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; font-weight: 600;">Siklus Uji</span>
                        </div>

                        <div style="margin-left: 24px; padding-left: 14px; border-left: 2px dashed #cbd5e1; margin-top: 6px; display: flex; flex-direction: column; gap: 8px;">
                            <!-- Subfolder 01 -->
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-folder text-amber-500" style="font-size: 15px;"></i>
                                    <span style="font-weight: 600; color: #0f172a;">01_Dokumen & Lembar Kerja</span>
                                </div>
                                <div style="margin-left: 26px; font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                                    <i class="fas fa-file-excel text-emerald-600"></i>
                                    <span id="treeReportFileName">Laporan_Testing.xlsx</span>
                                    <button type="button" onclick="copyText(document.getElementById('treeReportFileName').textContent, 'Nama file dokumen berhasil disalin!')" style="background: none; border: none; color: #2563eb; cursor: pointer; padding: 0 4px;" title="Salin nama file report">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Subfolder 02 -->
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-folder text-emerald-500" style="font-size: 15px;"></i>
                                    <span style="font-weight: 600; color: #0f172a;">02_Bukti Pengujian (Evidence)</span>
                                    <span style="font-size: 11px; background: #dcfce7; color: #166534; padding: 1px 6px; border-radius: 4px; font-weight: 600;">Screenshot & Video</span>
                                </div>
                                <div id="treeEvidencePreview" style="margin-left: 26px; font-size: 12px; color: #475569; margin-top: 4px; display: flex; flex-direction: column; gap: 2px;">
                                    <!-- Dynamic preview lines will be inserted here -->
                                </div>
                            </div>

                            <!-- Subfolder 03 -->
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-folder text-rose-500" style="font-size: 15px;"></i>
                                    <span style="font-weight: 600; color: #0f172a;">03_Tiket Bug & Rekap Isu</span>
                                    <span style="font-size: 11px; background: #fee2e2; color: #991b1b; padding: 1px 6px; border-radius: 4px; font-weight: 600;">Lampiran Buckleup</span>
                                </div>
                                <div style="margin-left: 26px; font-size: 11.5px; color: #94a3b8; margin-top: 2px;">
                                    Khusus bukti bug kritis/rekaman error yang dilampirkan langsung pada tiket Buckleup
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SOP Tips Alert -->
                <div style="margin-top: 18px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fas fa-lightbulb text-amber-500" style="font-size: 18px; margin-top: 2px;"></i>
                    <div style="font-size: 12.5px; color: #92400e; line-height: 1.6;">
                        <strong>Ketentuan SOP QA-WI-002:</strong>
                        <ul style="margin: 4px 0 0; padding-left: 18px;">
                            <li>Semua tangkapan layar (screenshot) bukti wajib menggunakan format <code>.png</code> atau <code>.jpg</code>.</li>
                            <li>Semua rekaman video pengujian flow wajib menggunakan format <code>.mp4</code>.</li>
                            <li>Pastikan folder Google Drive diatur permission <em>"Anyone with the link can view"</em> sebelum URL bukti ditempelkan ke kolom Evidence.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Evidence File Naming List -->
            <div id="tabContentList" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <span style="font-size: 13px; font-weight: 700; color: #1e293b;">Daftar Nama File Bukti Standar</span>
                        <div style="font-size: 11.5px; color: #64748b;">Format otomatis: <code>[TC_ID]_[Deskripsi_Singkat]_[Status]_[Tanggal].[ext]</code></div>
                    </div>
                    <button type="button" onclick="copyAllEvidenceFileNames()" style="background: #2563eb; color: white; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-copy"></i> Salin Semua Nama File Bukti
                    </button>
                </div>

                <div id="evidenceTableContainer" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #475569;">
                            <tr>
                                <th style="padding: 10px 14px; width: 60px;">No</th>
                                <th style="padding: 10px 14px; width: 140px;">TC ID</th>
                                <th style="padding: 10px 14px;">Judul / Skenario</th>
                                <th style="padding: 10px 14px; width: 90px;">Status</th>
                                <th style="padding: 10px 14px; width: 260px;">Nama File Standar (QA-WI-002)</th>
                                <th style="padding: 10px 14px; width: 70px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="evidenceTableBody">
                            <!-- Rows rendered by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="closeDriveModal()" style="background: #e2e8f0; color: #334155; border: none; padding: 9px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                Tutup
            </button>
        </div>

    </div>
</div>

@push('scripts')
<script>
(function() {
    'use strict';

    let currentModalData = {
        projectCode: '',
        projectName: '',
        sprint: '',
        cycle: '',
        reportName: '',
        date: '',
        testCases: []
    };

    window.openDriveModal = function(data) {
        currentModalData = Object.assign({
            projectCode: 'PROJ',
            projectName: 'Project System',
            sprint: 'Sprint 1',
            cycle: 'Siklus 1',
            reportName: 'Report Testing',
            date: new Date().toISOString().split('T')[0],
            testCases: []
        }, data || {});

        const projectFolder = `${currentModalData.projectCode}_${currentModalData.projectName}`;
        const cycleFolder = currentModalData.sprint ? `${currentModalData.sprint} - ${currentModalData.cycle}` : currentModalData.cycle;

        // Populate Context Banner
        document.getElementById('driveModalProjectName').textContent = projectFolder;
        document.getElementById('driveModalSprintCycle').textContent = cycleFolder;
        document.getElementById('driveModalCaseCount').textContent = `${currentModalData.testCases.length} Kasus Uji`;

        // Populate Tree
        document.getElementById('treeRootFolder').textContent = projectFolder;
        document.getElementById('treeSprintCycleFolder').textContent = cycleFolder;
        
        const cleanRepName = (currentModalData.reportName || 'Testing_Report').replace(/\s+/g, '_');
        document.getElementById('treeReportFileName').textContent = `${cleanRepName}.xlsx`;

        // Populate Tree Evidence Preview (first 3 items)
        const previewEl = document.getElementById('treeEvidencePreview');
        previewEl.innerHTML = '';
        const previewItems = currentModalData.testCases.slice(0, 3);
        if (previewItems.length > 0) {
            previewItems.forEach(tc => {
                const fn = generateStandardFileName(tc, currentModalData.date);
                const div = document.createElement('div');
                div.style.cssText = 'display:flex; align-items:center; gap:6px;';
                div.innerHTML = `<i class="fas fa-file-image text-blue-500"></i> <span>${escapeHtml(fn)}</span>`;
                previewEl.appendChild(div);
            });
            if (currentModalData.testCases.length > 3) {
                const moreDiv = document.createElement('div');
                moreDiv.style.cssText = 'color:#94a3b8; font-style:italic; margin-top:2px;';
                moreDiv.textContent = `... dan ${currentModalData.testCases.length - 3} file bukti lainnya (Lihat Tab 2)`;
                previewEl.appendChild(moreDiv);
            }
        } else {
            previewEl.innerHTML = `<span style="color:#94a3b8; font-style:italic;">Belum ada test case yang ditambahkan</span>`;
        }

        // Populate Evidence Table
        const tbody = document.getElementById('evidenceTableBody');
        tbody.innerHTML = '';
        if (currentModalData.testCases.length > 0) {
            currentModalData.testCases.forEach((tc, idx) => {
                const fn = generateStandardFileName(tc, currentModalData.date);
                const status = tc.status || 'Pass';
                const statusBadgeStyle = 
                    status === 'Pass' ? 'background:#dcfce7; color:#166534;' :
                    status === 'Fail' ? 'background:#fee2e2; color:#991b1b;' :
                    status === 'Blocked' ? 'background:#fef3c7; color:#92400e;' :
                    'background:#f1f5f9; color:#475569;';

                const tr = document.createElement('tr');
                tr.style.cssText = 'border-bottom: 1px solid #f1f5f9;';
                tr.innerHTML = `
                    <td style="padding: 10px 14px; color:#64748b; font-weight:600;">#${idx + 1}</td>
                    <td style="padding: 10px 14px; font-weight:700; color:#1e40af; font-family:monospace;">${escapeHtml(tc.testCaseId || `TC-${idx+1}`)}</td>
                    <td style="padding: 10px 14px; color:#1e293b; font-weight:500;">
                        ${escapeHtml(tc.title || '(Tanpa Judul)')}
                        ${tc.bugTicket ? `<span style="display:inline-block; font-size:11px; font-weight:700; background:#fee2e2; color:#991b1b; padding:1px 6px; border-radius:4px; margin-left:6px;">🐛 ${escapeHtml(tc.bugTicket)}</span>` : ''}
                    </td>
                    <td style="padding: 10px 14px;">
                        <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; ${statusBadgeStyle}">
                            ${status}
                        </span>
                    </td>
                    <td style="padding: 10px 14px; font-family:monospace; font-size:12px; color:#0f172a; word-break:break-all;">
                        ${escapeHtml(fn)}
                    </td>
                    <td style="padding: 10px 14px; text-align:center;">
                        <button type="button" onclick="copyText('${escapeJsString(fn)}', 'Nama file bukti disalin!')" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; padding:4px 8px; border-radius:6px; font-size:11.5px; font-weight:600; cursor:pointer;" title="Salin Nama File">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="padding:24px; text-align:center; color:#94a3b8;">Belum ada test case pada report ini.</td></tr>`;
        }

        switchDriveTab('tree');
        document.getElementById('driveStructureModal').style.display = 'block';
    };

    window.closeDriveModal = function() {
        document.getElementById('driveStructureModal').style.display = 'none';
    };

    window.switchDriveTab = function(tab) {
        const treeTab = document.getElementById('tabContentTree');
        const listTab = document.getElementById('tabContentList');
        const btnTree = document.getElementById('tabBtnTree');
        const btnList = document.getElementById('tabBtnList');

        if (tab === 'tree') {
            treeTab.style.display = 'block';
            listTab.style.display = 'none';
            btnTree.style.color = '#2563eb';
            btnTree.style.borderBottomColor = '#2563eb';
            btnTree.style.fontWeight = '700';
            btnList.style.color = '#64748b';
            btnList.style.borderBottomColor = 'transparent';
            btnList.style.fontWeight = '600';
        } else {
            treeTab.style.display = 'none';
            listTab.style.display = 'block';
            btnList.style.color = '#2563eb';
            btnList.style.borderBottomColor = '#2563eb';
            btnList.style.fontWeight = '700';
            btnTree.style.color = '#64748b';
            btnTree.style.borderBottomColor = 'transparent';
            btnTree.style.fontWeight = '600';
        }
    };

    // Helper: generate filename per QA-WI-002
    function generateStandardFileName(tc, dateStr) {
        const tcId = (tc.testCaseId || 'TC-01').trim().replace(/[^a-zA-Z0-9\-_]/g, '');
        let titleSlug = (tc.title || 'Test')
            .trim()
            .replace(/[^a-zA-Z0-9\s\-_]/g, '')
            .replace(/\s+/g, '_')
            .slice(0, 32);
        if (!titleSlug) titleSlug = 'Test';

        const status = tc.status || 'Pass';
        const date = dateStr || new Date().toISOString().split('T')[0];
        const bugTicket = (tc.bugTicket || '').trim().replace(/[^a-zA-Z0-9\-_]/g, '');

        if (status === 'Fail' && bugTicket) {
            return `${bugTicket}_${tcId}_${titleSlug}_${status}_${date}.png`;
        }
        return `${tcId}_${titleSlug}_${status}_${date}.png`;
    }
    window.generateStandardFileName = generateStandardFileName;

    window.copyDriveFolderPath = function() {
        const projectFolder = `${currentModalData.projectCode}_${currentModalData.projectName}`;
        const cycleFolder = currentModalData.sprint ? `${currentModalData.sprint} - ${currentModalData.cycle}` : currentModalData.cycle;
        const fullPath = `${projectFolder}/${cycleFolder}/02_Bukti Pengujian (Evidence)`;
        copyText(fullPath, 'Path folder bukti Google Drive berhasil disalin!');
    };

    window.copyMkdirCommand = function() {
        const projectFolder = `${currentModalData.projectCode}_${currentModalData.projectName}`.replace(/["$`\\]/g, '');
        const cycleFolder = (currentModalData.sprint ? `${currentModalData.sprint} - ${currentModalData.cycle}` : currentModalData.cycle).replace(/["$`\\]/g, '');
        const cmd = `mkdir -p "${projectFolder}/${cycleFolder}/01_Dokumen & Lembar Kerja" "${projectFolder}/${cycleFolder}/02_Bukti Pengujian (Evidence)" "${projectFolder}/${cycleFolder}/03_Tiket Bug & Rekap Isu"`;
        copyText(cmd, 'Perintah mkdir terminal berhasil disalin!');
    };

    window.copyAllEvidenceFileNames = function() {
        if (!currentModalData.testCases || currentModalData.testCases.length === 0) {
            alert('Tidak ada file bukti untuk disalin.');
            return;
        }
        const names = currentModalData.testCases.map(tc => generateStandardFileName(tc, currentModalData.date));
        copyText(names.join("\n"), `${names.length} nama file bukti berhasil disalin ke clipboard!`);
    };

    window.copyText = function(text, successMsg) {
        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        } else {
            navigator.clipboard.writeText(text);
        }

        showDriveToast(successMsg || 'Berhasil disalin ke clipboard!');
    };

    function showDriveToast(msg) {
        let toast = document.getElementById('driveModalToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'driveModalToast';
            toast.style.cssText = 'position:fixed; bottom:24px; right:24px; background:#0f172a; color:#f8fafc; padding:12px 20px; border-radius:12px; font-size:13px; font-weight:600; box-shadow:0 10px 25px -5px rgba(0,0,0,0.3); z-index:99999; display:flex; align-items:center; gap:8px; border:1px solid #334155; transition:all 0.3s ease; opacity:0; transform:translateY(10px);';
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i class="fas fa-check-circle text-emerald-400"></i> ${escapeHtml(msg)}`;
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
        }, 3000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeJsString(str) {
        if (!str) return '';
        return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const m = document.getElementById('driveStructureModal');
            if (m && m.style.display !== 'none') {
                closeDriveModal();
            }
        }
    });

})();
</script>
@endpush
