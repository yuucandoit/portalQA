<?php

namespace App\Exports\QaTpl001;

use App\Models\TestReport;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TcHardwareSheet implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    private TestReport $report;

    public function __construct(TestReport $report)
    {
        $this->report = $report;
    }

    public function title(): string
    {
        return 'TC_Hardware';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── 1. Header Banner (Row 1) ──────────────────────────────────
                $sheet->mergeCells('A1:S1');
                $sheet->setCellValue('A1', 'TEST CASE HARDWARE — Master Template v0.2');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // ── 2. Metadata Box (Rows 3-8) ────────────────────────────────
                $meta = [
                    3 => ['A' => 'Produk & Model', 'B' => $this->report->project->name ?? 'Hardware Unit', 'F' => 'Kondisi Lingkungan', 'G' => $this->report->description ?? 'Outdoor / Suhu Ambien ~30°C'],
                    4 => ['A' => 'Serial Number / Unit ID', 'B' => 'Unit-001', 'F' => 'Alat Ukur yang Dipakai', 'G' => 'Multimeter / RF Analyzer / Power Meter'],
                    5 => ['A' => 'Versi Firmware / Software', 'B' => $this->report->sprint ?? 'v1.0.0', 'F' => 'Tester', 'G' => $this->report->createdBy->name ?? 'QA Engineer'],
                    6 => ['A' => 'Jenis Testing', 'B' => $this->report->test_type_label, 'F' => 'PIC Engineer / Saksi', 'G' => 'Hardware QA / Tech Lead'],
                    7 => ['A' => 'Referensi Spesifikasi', 'B' => 'Datasheet & TOR Teknis', 'F' => 'Tanggal & Waktu Uji', 'G' => $this->report->report_date ? $this->report->report_date->format('d/m/Y') : '-'],
                    8 => ['A' => 'Lokasi Uji', 'B' => 'Lab QA & Flight Test Field', 'F' => 'Siklus', 'G' => $this->report->cycle ?? 'Siklus 1'],
                ];

                foreach ($meta as $r => $cols) {
                    $sheet->setCellValue("A{$r}", $cols['A']);
                    $sheet->setCellValue("B{$r}", $cols['B']);
                    $sheet->setCellValue("F{$r}", $cols['F']);
                    $sheet->setCellValue("G{$r}", $cols['G']);

                    $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("F{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
                    $sheet->getStyle("F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
                }

                // ── 3. Table Column Headers (Row 13) ──────────────────────────
                $colsMap = [
                    'A' => 'TC ID',
                    'B' => 'Komponen / Subsistem',
                    'C' => 'Scenario',
                    'D' => 'Tipe',
                    'E' => 'Criticality',
                    'F' => 'Precondition (kondisi device)',
                    'G' => 'Test Steps',
                    'H' => 'Parameter Diukur',
                    'I' => 'Satuan',
                    'J' => 'Batas Lulus (Spec)',
                    'K' => 'Sumber Spec',
                    'L' => 'Expected Result',
                    'M' => 'Nilai Terukur',
                    'N' => 'Actual Result',
                    'O' => 'Status',
                    'P' => 'Evidence',
                    'Q' => 'Bug ID',
                    'R' => 'Tester',
                    'S' => 'Notes',
                ];

                $sheet->getRowDimension(13)->setRowHeight(28);
                foreach ($colsMap as $col => $title) {
                    $sheet->setCellValue("{$col}13", $title);
                }

                $sheet->getStyle('A13:S13')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0369A1']], // Ocean Blue
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
                ]);

                // ── 4. Populate Hardware Test Cases ───────────────────────────
                $testCases = $this->report->test_type === 'hardware'
                    ? $this->report->testCases()->with('steps')->get()
                    : collect();

                $currRow = 14;

                if ($testCases->isEmpty()) {
                    // Provide empty reference row
                    $sheet->setCellValue("A{$currRow}", 'DRN-PWR-001');
                    $sheet->setCellValue("B{$currRow}", 'Docking – Charging');
                    $sheet->setCellValue("C{$currRow}", 'Charging sequence aktif otomatis setelah drone mendarat di docking');
                    $sheet->setCellValue("D{$currRow}", 'Positive');
                    $sheet->setCellValue("E{$currRow}", 'High');
                    $sheet->setCellValue("F{$currRow}", 'Drone selesai misi dan mendarat di landing pad; docking Power ON');
                    $sheet->setCellValue("G{$currRow}", "1. Jalankan misi sampai drone mendarat\n2. Amati apakah charge sequence aktif otomatis\n3. Ulangi untuk setiap siklus");
                    $sheet->setCellValue("H{$currRow}", 'Keberhasilan charge sequence otomatis');
                    $sheet->setCellValue("I{$currRow}", 'siklus');
                    $sheet->setCellValue("J{$currRow}", '8/8 siklus berhasil');
                    $sheet->setCellValue("K{$currRow}", 'Datasheet');
                    $sheet->setCellValue("L{$currRow}", 'Charge sequence aktif otomatis di setiap pendaratan');
                    $sheet->setCellValue("M{$currRow}", '6/8');
                    $sheet->setCellValue("N{$currRow}", '2 kali charge sequence tidak aktif otomatis');
                    $sheet->setCellValue("O{$currRow}", 'Fail');
                    $sheet->setCellValue("P{$currRow}", '-');
                    $sheet->setCellValue("Q{$currRow}", 'BUG-001');
                    $sheet->setCellValue("R{$currRow}", 'QA');
                    $sheet->setCellValue("S{$currRow}", 'Template Hardware');
                    $sheet->getStyle("A{$currRow}:S{$currRow}")->applyFromArray([
                        'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    ]);
                    $currRow++;
                } else {
                    foreach ($testCases as $idx => $tc) {
                        $dyn = is_array($tc->dynamic_fields) ? $tc->dynamic_fields : (json_decode($tc->dynamic_fields, true) ?? []);

                        // Build steps and expected text
                        $stepTexts = [];
                        $expectedTexts = [];
                        foreach ($tc->steps as $sIdx => $st) {
                            $stepTexts[] = ($sIdx + 1) . '. ' . $st->step_description;
                            if (!empty($st->expected_result)) {
                                $expectedTexts[] = ($sIdx + 1) . '. ' . $st->expected_result;
                            }
                        }

                        $stepString = !empty($stepTexts) ? implode("\n", $stepTexts) : 'Uji parameter hardware sesuai skenario';
                        $expString  = !empty($expectedTexts) ? implode("\n", $expectedTexts) : 'Nilai parameter memenuhi batas spesifikasi';

                        $sheet->setCellValue("A{$currRow}", $tc->test_case_id ?: 'TC-HW-' . sprintf('%03d', $idx + 1));
                        $sheet->setCellValue("B{$currRow}", $dyn['componentSubsystem'] ?? $dyn['deviceTested'] ?? 'Hardware Subsystem');
                        $sheet->setCellValue("C{$currRow}", $tc->title);
                        $sheet->setCellValue("D{$currRow}", $dyn['caseType'] ?? 'Positive');
                        $sheet->setCellValue("E{$currRow}", $tc->priority ?: 'High');
                        $sheet->setCellValue("F{$currRow}", $tc->precondition ?? '');
                        $sheet->setCellValue("G{$currRow}", $stepString);
                        $sheet->setCellValue("H{$currRow}", $dyn['parameterMeasured'] ?? '');
                        $sheet->setCellValue("I{$currRow}", $dyn['unit'] ?? '');
                        $sheet->setCellValue("J{$currRow}", $dyn['passLimitSpec'] ?? '');
                        $sheet->setCellValue("K{$currRow}", $dyn['specSource'] ?? '');
                        $sheet->setCellValue("L{$currRow}", $expString);
                        $sheet->setCellValue("M{$currRow}", $dyn['measuredValue'] ?? '');
                        $sheet->setCellValue("N{$currRow}", $tc->actual_result ?? '');
                        $sheet->setCellValue("O{$currRow}", $tc->status);
                        $sheet->setCellValue("P{$currRow}", $tc->evidence_url ?? '');
                        $sheet->setCellValue("Q{$currRow}", $tc->bug_ticket ?? '');
                        $sheet->setCellValue("R{$currRow}", $this->report->createdBy->name ?? 'QA');
                        $sheet->setCellValue("S{$currRow}", $tc->notes_qa ?? $tc->notes_programmer ?? '');

                        // Status Color Styling
                        $stColor = match($tc->status) {
                            'Pass', 'PASS'           => 'DCFCE7',
                            'Fail', 'FAIL', 'FAILED' => 'FEE2E2',
                            'Blocked', 'BLOCKED'     => 'FEF3C7',
                            default                  => 'F1F5F9',
                        };
                        $sheet->getStyle("O{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($stColor);
                        $sheet->getStyle("O{$currRow}")->getFont()->setBold(true);

                        $sheet->getStyle("A{$currRow}:S{$currRow}")->applyFromArray([
                            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                        ]);

                        $currRow++;
                    }
                }

                $sheet->getColumnDimension('A')->setWidth(16);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('G')->setWidth(32);
                $sheet->getColumnDimension('H')->setWidth(24);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('L')->setWidth(28);
                $sheet->getColumnDimension('M')->setWidth(16);
                $sheet->getColumnDimension('N')->setWidth(28);
            },
        ];
    }
}
