<?php

namespace App\Exports\QaTpl001;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PanduanSheet implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    public function title(): string
    {
        return 'Panduan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── Row 1: Title ──────────────────────────────────────────────
                $sheet->mergeCells('A1:B1');
                $sheet->setCellValue('A1', 'PANDUAN QA TEST CASE & BUG LIST — Software & Hardware');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // ── Row 2: Doc Metadata ───────────────────────────────────────
                $sheet->mergeCells('A2:B2');
                $sheet->setCellValue('A2', 'Dokumen: QA-TPL-001  |  Versi: Master v0.2  |  Status: RESMI (QA Portal)  |  Sistem: Multi-Platform Software & Hardware');
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '475569']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $data = [
                    ['', ''],
                    ['0. CARA PAKAI MASTER TEMPLATE', ''],
                    ['File ini', 'MASTER TEMPLATE QA-TPL-001 standar QA Engineering. Setiap eksekusi pengujian software dan hardware wajib mengikuti konvensi pada file ini.'],
                    ['Penamaan File', 'Gunakan format standar: [Kode Project]_TC_BugList_[Siklus]_[YYYYMMDD].xlsx (misal: PRJ-01_TC_BugList_Siklus1_20261015.xlsx).'],
                    ['Integritas Struktur', 'Dilarang menghapus atau mengubah urutan kolom header biru. Rumus ringkasan dan Quality Gate terhubung langsung ke kolom Status dan Bug ID.'],
                    ['', ''],
                    ['1. ISI DAN STRUKTUR SHEET', ''],
                    ['Panduan', 'Aturan main, konvensi penulisan, definisi status, dan Quality Gate release checklist.'],
                    ['TC_Software', 'Template pencatatan pengujian fungsional modul, API, form, dan logika bisnis aplikasi web/mobile/desktop.'],
                    ['TC_Hardware', 'Template pencatatan pengujian perangkat keras (RF, power, docking, suhu, sensor, antena, mekanik, navigasi).'],
                    ['Library_Parameter_HW', 'Pustaka 33 parameter baku hardware QA dengan satuan default, contoh batas lulus, dan sumber spesifikasi.'],
                    ['Bug List', 'Pencatatan defect/bug lengkap dengan Severity, Priority, Status, Environment, Dev Assignee, dan verifikasi retest QA.'],
                    ['Ringkasan_TC', 'Rekap otomatis matriks status test case, Pass Rate, progress eksekusi, serta pengecekan kelengkapan Quality Gate.'],
                    ['Summary_Bug', 'Rekapitulasi defect matriks Severity × Status dan indikator kesehatan bug tracking.'],
                    ['', ''],
                    ['2. ATURAN WAJIB (RULES 1 - 8)', ''],
                    ['Aturan 1 (Header)', 'Header metadata dokumen (Project, Sprint, Siklus, Tester) WAJIB terisi lengkap sebelum testing dimulai.'],
                    ['Aturan 2 (Status Tunggal)', 'Satu baris test case hanya memiliki SATU status: Not Run, Pass, Fail, Blocked, atau N/A. Tidak ada checklist ganda.'],
                    ['Aturan 3 (Defect Tracing)', 'Status Fail WAJIB memiliki Bug ID yang tercatat pada Bug List, dan bug tersebut wajib mengisi Related TC ID (Two-Way Tracing).'],
                    ['Aturan 4 (Evidence Bukti)', 'Setiap test case yang dieksekusi (Pass/Fail) WAJIB melampirkan Evidence (URL screenshot, video rekaman Loom, atau log).'],
                    ['Aturan 5 (Actual Result)', 'Hasil aktual ditulis sesuai fakta objektif yang terlihat/terukur di lapangan, bukan sekadar kalimat opini seperti "berjalan lancar".'],
                    ['Aturan 6 (Struktur Langkah)', 'Precondition berisi kondisi awal sebelum uji. Test Steps berisi aksi berurutan. Nilai masukan konkret ditulis di Test Data.'],
                    ['Aturan 7 (Hardware Spec)', 'Parameter hardware tanpa Batas Lulus / Spec kuantitatif TIDAK BOLEH berstatus Pass. Selama belum ada, status = Blocked.'],
                    ['Aturan 8 (Hardware Unit)', 'Unit ID / Serial Number perangkat dan versi firmware WAJIB dicatat pada pengujian hardware.'],
                ];

                $r = 3;
                foreach ($data as $row) {
                    $sheet->setCellValue("A{$r}", $row[0]);
                    $sheet->setCellValue("B{$r}", $row[1]);

                    if (str_starts_with($row[0], '0.') || str_starts_with($row[0], '1.') || str_starts_with($row[0], '2.')) {
                        $sheet->mergeCells("A{$r}:B{$r}");
                        $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E293B']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        ]);
                        $sheet->getRowDimension($r)->setRowHeight(24);
                    } elseif (!empty($row[0])) {
                        $sheet->getStyle("A{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0284C7']],
                        ]);
                        $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setWrapText(true);
                    }
                    $r++;
                }

                $sheet->getColumnDimension('A')->setWidth(26);
                $sheet->getColumnDimension('B')->setWidth(95);
            },
        ];
    }
}
