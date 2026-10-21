<!-- ===== LIBRARY PARAMETER HARDWARE MODAL (QA-TPL-001) ===== -->
<div id="hwLibraryModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: white; border-radius: 20px; width: 100%; max-width: 850px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
        <!-- Header Modal -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="fas fa-microchip"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1e293b;">Library Parameter Hardware (QA-TPL-001)</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Pilih parameter terstandarisasi untuk mengisi komponen, satuan & batas spec</p>
                </div>
            </div>
            <button type="button" onclick="closeHwLibraryModal()" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer; padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div style="padding: 14px 24px; border-bottom: 1px solid #e2e8f0; background: white; display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 11px; color: #94a3b8;"></i>
                <input type="text" id="hwLibSearchInput" onkeyup="filterHwLibrary()" placeholder="Cari nama parameter, satuan, atau cara ukur..."
                       style="width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none;">
            </div>
            <div style="min-width: 180px;">
                <select id="hwLibCategoryFilter" onchange="filterHwLibrary()" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: white;">
                    <option value="">Semua Kategori</option>
                    <option value="Daya & Baterai">Daya & Baterai</option>
                    <option value="Thermal">Thermal</option>
                    <option value="Penerbangan & Navigasi">Penerbangan & Navigasi</option>
                    <option value="Komunikasi">Komunikasi</option>
                    <option value="Kamera & Media">Kamera & Media</option>
                    <option value="Mekanik & Docking">Mekanik & Docking</option>
                    <option value="Anti-drone">Anti-drone</option>
                    <option value="Lingkungan">Lingkungan</option>
                    <option value="Reliability">Reliability</option>
                </select>
            </div>
        </div>

        <!-- Table Parameter List -->
        <div style="padding: 0; overflow-y: auto; flex: 1;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th style="padding: 10px 18px; color: #475569; font-size: 12px;">Kategori</th>
                        <th style="padding: 10px 14px; color: #475569; font-size: 12px;">Parameter Diukur</th>
                        <th style="padding: 10px 14px; color: #475569; font-size: 12px;">Satuan</th>
                        <th style="padding: 10px 14px; color: #475569; font-size: 12px;">Berlaku Untuk</th>
                        <th style="padding: 10px 14px; color: #475569; font-size: 12px;">Cara Ukur</th>
                        <th style="padding: 10px 18px; text-align: right; color: #475569; font-size: 12px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="hwLibTableBody">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>

        <!-- Footer Modal -->
        <div style="padding: 12px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; font-size: 12px; color: #64748b;">
            <span>Standar Library QA-TPL-001 (33 Parameter)</span>
            <button type="button" onclick="closeHwLibraryModal()" style="padding: 6px 14px; border: 1px solid #cbd5e1; background: white; color: #475569; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer;">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    window.HW_LIBRARY_DATA = @json(\App\Services\HardwareParameterLibrary::all());
    let currentTargetTestIdx = null;

    window.openHwLibraryModal = function(testIdx) {
        currentTargetTestIdx = testIdx;
        const modal = document.getElementById('hwLibraryModal');
        if (!modal) return;
        renderHwLibraryTable();
        modal.style.display = 'flex';
    };

    window.closeHwLibraryModal = function() {
        const modal = document.getElementById('hwLibraryModal');
        if (modal) modal.style.display = 'none';
        currentTargetTestIdx = null;
    };

    function renderHwLibraryTable() {
        const tbody = document.getElementById('hwLibTableBody');
        const search = (document.getElementById('hwLibSearchInput')?.value || '').toLowerCase().trim();
        const category = document.getElementById('hwLibCategoryFilter')?.value || '';

        const filtered = window.HW_LIBRARY_DATA.filter(item => {
            const matchCat = !category || item.kategori === category;
            const matchSearch = !search || 
                item.parameter.toLowerCase().includes(search) || 
                item.satuan.toLowerCase().includes(search) || 
                item.cara_ukur.toLowerCase().includes(search) ||
                item.berlaku_untuk.toLowerCase().includes(search);
            return matchCat && matchSearch;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                        Tidak ada parameter yang sesuai pencarian.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map((item, idx) => `
            <tr style="border-bottom: 1px solid #f1f5f9; cursor: pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                <td style="padding: 10px 18px; font-size: 12px; font-weight: 600; color: #64748b;">
                    <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">${item.kategori}</span>
                </td>
                <td style="padding: 10px 14px; font-weight: 700; color: #1e293b;">${item.parameter}</td>
                <td style="padding: 10px 14px;">
                    <code style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 700;">${item.satuan}</code>
                </td>
                <td style="padding: 10px 14px; font-size: 12px; color: #475569;">${item.berlaku_untuk}</td>
                <td style="padding: 10px 14px; font-size: 12px; color: #64748b;">${item.cara_ukur}</td>
                <td style="padding: 10px 18px; text-align: right;">
                    <button type="button" onclick='selectHwParameter(${JSON.stringify(item)})' 
                            style="background: #2563eb; color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                        Pilih
                    </button>
                </td>
            </tr>
        `).join('');
    }

    window.filterHwLibrary = renderHwLibraryTable;

    window.selectHwParameter = function(item) {
        if (currentTargetTestIdx === null) return;
        const testIdx = currentTargetTestIdx;

        // Apply fields to test case row
        const paramInput = document.getElementById(`parameterMeasured_${testIdx}`);
        if (paramInput) paramInput.value = item.parameter;

        const unitInput = document.getElementById(`unit_${testIdx}`);
        if (unitInput) unitInput.value = item.satuan;

        const compInput = document.getElementById(`componentSubsystem_${testIdx}`);
        if (compInput && !compInput.value.trim()) compInput.value = `${item.berlaku_untuk} – ${item.kategori}`;

        const specSourceInput = document.getElementById(`specSource_${testIdx}`);
        if (specSourceInput && !specSourceInput.value.trim()) specSourceInput.value = item.cara_ukur;

        const passLimitInput = document.getElementById(`passLimitSpec_${testIdx}`);
        if (passLimitInput && !passLimitInput.value.trim() && item.batas_lulus) {
            passLimitInput.value = item.batas_lulus;
        }

        closeHwLibraryModal();
    };
</script>
