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

class TcSoftwareSheet implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    private TestReport $report;

    public function __construct(TestReport $report)
    {
        $this->report = $report;
    }

    public function title(): string
    {
        return 'TC_Software';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── 1. Header Banner (Row 1) ──────────────────────────────────
                $sheet->mergeCells('A1:R1');
                $sheet->setCellValue('A1', 'TEST CASE SOFTWARE — Master Template v0.2');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // ── 2. Metadata Box (Rows 3-7) ────────────────────────────────
                $meta = [
                    3 => ['A' => 'Project / Produk', 'B' => $this->report->project->name ?? 'General', 'F' => 'Referensi Requirement', 'G' => $this->report->project->project_code ?? '-'],
                    4 => ['A' => 'Modul / Laporan', 'B' => $this->report->report_name, 'F' => 'Device / Browser', 'G' => 'Desktop / Mobile Web'],
                    5 => ['A' => 'Versi / Sprint', 'B' => $this->report->sprint ?? '-', 'F' => 'Tester', 'G' => $this->report->createdBy->name ?? 'QA Engineer'],
                    6 => ['A' => 'Siklus', 'B' => $this->report->cycle ?? 'Siklus 1', 'F' => 'Tanggal Uji', 'G' => $this->report->report_date ? $this->report->report_date->format('d/m/Y') : '-'],
                    7 => ['A' => 'Jenis Testing', 'B' => $this->report->test_type_label, 'F' => 'Environment', 'G' => 'Staging / SIT'],
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

                // ── 3. Table Column Headers (Row 11) ──────────────────────────
                $headers = [
                    'A' => 'TC ID',
                    'B' => 'Modul / Menu',
                    'C' => 'Fitur',
                    'D' => 'Ref Requirement',
                    'E' => 'Scenario',
                    'F' => 'Tipe',
                    'G' => 'Priority',
                    'H' => 'Precondition',
                    'I' => 'Test Data',
                    'J' => 'Test Steps',
                    'Expected Result' => 'K',
                    'Actual Result'   => 'L',
                    'Status'          => 'M',
                    'Evidence'        => 'N',
                    'Bug ID'          => 'O',
                    'Tester'          => 'P',
                    'Tanggal Test'    => 'Q',
                    'Notes'           => 'R',
                ];

                // Direct mapping
                $colsMap = [
                    'A' => 'TC ID',
                    'B' => 'Modul / Menu',
                    'C' => 'Fitur',
                    'D' => 'Ref Requirement',
                    'E' => 'Scenario',
                    'F' => 'Tipe',
                    'G' => 'Priority',
                    'H' => 'Precondition',
                    'I' => 'Test Data',
                    'J' => 'Test Steps',
                    'K' => 'Expected Result',
                    'L' => 'Actual Result',
                    'M' => 'Status',
                    'N' => 'Evidence',
                    'O' => 'Bug ID',
                    'P' => 'Tester',
                    'Q' => 'Tanggal Test',
                    'R' => 'Notes',
                ];

                $sheet->getRowDimension(11)->setRowHeight(28);
                foreach ($colsMap as $col => $title) {
                    $sheet->setCellValue("{$col}11", $title);
                }

                $sheet->getStyle('A11:R11')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']], // Royal Blue
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '93C5FD']]],
                ]);

                // ── 4. Populate Test Cases ────────────────────────────────────
                $testCases = $this->report->test_type !== 'hardware'
                    ? $this->report->testCases()->with('steps')->get()
                    : collect();

                $currRow = 12;

                if ($testCases->isEmpty()) {
                    // Provide empty reference row
                    $sheet->setCellValue("A{$currRow}", 'SPC-TC-001');
                    $sheet->setCellValue("B{$currRow}", 'Modul Contoh');
                    $sheet->setCellValue("C{$currRow}", 'Fitur Contoh');
                    $sheet->setCellValue("D{$currRow}", 'REQ-01');
                    $sheet->setCellValue("E{$currRow}", 'Verifikasi skenario login valid');
                    $sheet->setCellValue("F{$currRow}", 'Positive');
                    $sheet->setCellValue("G{$currRow}", 'High');
                    $sheet->setCellValue("H{$currRow}", 'User pada halaman login');
                    $sheet->setCellValue("I{$currRow}", 'username: admin, pass: secret');
                    $sheet->setCellValue("J{$currRow}", "1. Buka halaman login\n2. Masukkan kredensial\n3. Klik Login");
                    $sheet->setCellValue("K{$currRow}", 'Login berhasil masuk dashboard');
                    $sheet->setCellValue("L{$currRow}", 'Sesuai ekspektasi');
                    $sheet->setCellValue("M{$currRow}", 'Pass');
                    $sheet->setCellValue("N{$currRow}", '-');
                    $sheet->setCellValue("O{$currRow}", '-');
                    $sheet->setCellValue("P{$currRow}", 'QA');
                    $sheet->setCellValue("Q{$currRow}", date('d/m/Y'));
                    $sheet->setCellValue("R{$currRow}", 'Template Software');
                    $sheet->getStyle("A{$currRow}:R{$currRow}")->applyFromArray([
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

                        $stepString = !empty($stepTexts) ? implode("\n", $stepTexts) : 'Lakukan pengujian sesuai judul skenario';
                        $expString  = !empty($expectedTexts) ? implode("\n", $expectedTexts) : 'Sistem berfungsi sesuai spesifikasi';

                        $sheet->setCellValue("A{$currRow}", $tc->test_case_id ?: 'TC-' . sprintf('%03d', $idx + 1));
                        $sheet->setCellValue("B{$currRow}", $dyn['moduleFeature'] ?? 'General');
                        $sheet->setCellValue("C{$currRow}", $dyn['feature'] ?? '');
                        $sheet->setCellValue("D{$currRow}", $dyn['refRequirement'] ?? '');
                        $sheet->setCellValue("E{$currRow}", $tc->title);
                        $sheet->setCellValue("F{$currRow}", $dyn['caseType'] ?? 'Positive');
                        $sheet->setCellValue("G{$currRow}", $tc->priority ?: 'Medium');
                        $sheet->setCellValue("H{$currRow}", $tc->precondition ?? '');
                        $sheet->setCellValue("I{$currRow}", $dyn['testData'] ?? '');
                        $sheet->setCellValue("J{$currRow}", $stepString);
                        $sheet->setCellValue("K{$currRow}", $expString);
                        $sheet->setCellValue("L{$currRow}", $tc->actual_result ?? '');
                        $sheet->setCellValue("M{$currRow}", $tc->status);
                        $sheet->setCellValue("N{$currRow}", $tc->evidence_url ?? '');
                        $sheet->setCellValue("O{$currRow}", $tc->bug_ticket ?? '');
                        $sheet->setCellValue("P{$currRow}", $this->report->createdBy->name ?? 'QA');
                        $sheet->setCellValue("Q{$currRow}", $tc->date_tested ? $tc->date_tested->format('d/m/Y') : ($this->report->report_date ? $this->report->report_date->format('d/m/Y') : ''));
                        $sheet->setCellValue("R{$currRow}", $tc->notes_qa ?? $tc->notes_programmer ?? '');

                        // Status Color Styling
                        $stColor = match($tc->status) {
                            'Pass', 'PASS'           => 'DCFCE7', // soft green
                            'Fail', 'FAIL', 'FAILED' => 'FEE2E2', // soft red
                            'Blocked', 'BLOCKED'     => 'FEF3C7', // soft yellow
                            default                  => 'F1F5F9', // soft gray
                        };
                        $sheet->getStyle("M{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($stColor);
                        $sheet->getStyle("M{$currRow}")->getFont()->setBold(true);

                        $sheet->getStyle("A{$currRow}:R{$currRow}")->applyFromArray([
                            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                        ]);

                        $currRow++;
                    }
                }

                $sheet->getColumnDimension('A')->setWidth(16);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('J')->setWidth(35);
                $sheet->getColumnDimension('K')->setWidth(30);
                $sheet->getColumnDimension('L')->setWidth(30);
            },
        ];
    }
}
