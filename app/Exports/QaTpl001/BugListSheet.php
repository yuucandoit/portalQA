<?php

namespace App\Exports\QaTpl001;

use App\Models\Bug;
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

class BugListSheet implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    private TestReport $report;

    public function __construct(TestReport $report)
    {
        $this->report = $report;
    }

    public function title(): string
    {
        return 'Bug List';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── 1. Header Banner (Row 1) ──────────────────────────────────
                $sheet->mergeCells('A1:S1');
                $sheet->setCellValue('A1', 'BUG LIST (QA-TPL-001 Master v0.2)');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // ── 2. Project Metadata (Row 2) ───────────────────────────────
                $sheet->mergeCells('A2:S2');
                $sheet->setCellValue('A2', "Project: {$this->report->project->name} ({$this->report->project->project_code}) | Modul: {$this->report->report_name}");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '475569']],
                ]);

                // ── 3. Table Column Headers (Row 4 & 5) ───────────────────────
                // Row 4: Section tags
                $sheet->mergeCells('A4:P4');
                $sheet->setCellValue('A4', 'DIISI OLEH QA');
                $sheet->mergeCells('Q4:Q4');
                $sheet->setCellValue('Q4', 'QA & DEV');
                $sheet->mergeCells('R4:S4');
                $sheet->setCellValue('R4', 'DIISI OLEH DEV / QA RETEST');

                $sheet->getStyle('A4:P4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E40AF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('Q4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '92400E']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('R4:S4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '065F46']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Row 5: Column headers
                $colsMap = [
                    'A' => 'Bug ID',
                    'B' => 'Tanggal Ditemukan',
                    'C' => 'Modul / Fitur',
                    'D' => 'Bug Type',
                    'E' => 'Judul Bug',
                    'F' => 'Langkah Reproduksi',
                    'G' => 'Expected Result',
                    'H' => 'Actual Result',
                    'I' => 'Severity',
                    'J' => 'Priority',
                    'K' => 'Environment',
                    'L' => 'Device / Unit ID',
                    'M' => 'Evidence (Link)',
                    'N' => 'Related TC ID',
                    'O' => 'Reported By',
                    'P' => 'Assigned To (Dev)',
                    'Q' => 'Status',
                    'R' => 'Tanggal Fix',
                    'S' => 'Catatan QA Retest',
                ];

                $sheet->getRowDimension(5)->setRowHeight(28);
                foreach ($colsMap as $col => $title) {
                    $sheet->setCellValue("{$col}5", $title);
                }

                $sheet->getStyle('A5:S5')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
                ]);

                // ── 4. Populate Bugs ──────────────────────────────────────────
                $tcIds = $this->report->testCases->pluck('test_case_id')->filter()->values()->all();
                $bugs = Bug::where('report_id', $this->report->id)
                    ->orWhere(function ($q) use ($tcIds) {
                        $q->where('project_id', $this->report->project_id);
                        if (!empty($tcIds)) {
                            $q->whereIn('related_test_case_id', $tcIds);
                        }
                    })
                    ->orderBy('id', 'asc')
                    ->get();

                $currRow = 6;

                if ($bugs->isEmpty()) {
                    // Sample/Placeholder row
                    $sheet->setCellValue("A{$currRow}", 'BUG-001');
                    $sheet->setCellValue("B{$currRow}", date('d/m/Y'));
                    $sheet->setCellValue("C{$currRow}", 'Auth / Login');
                    $sheet->setCellValue("D{$currRow}", 'Functional Bug');
                    $sheet->setCellValue("E{$currRow}", 'Pesan validasi tidak muncul saat password salah');
                    $sheet->setCellValue("F{$currRow}", "1. Buka halaman login\n2. Isi email valid\n3. Isi password salah\n4. Klik Sign In");
                    $sheet->setCellValue("G{$currRow}", 'Muncul alert error "Password salah"');
                    $sheet->setCellValue("H{$currRow}", 'Halaman diam saja tanpa respons');
                    $sheet->setCellValue("I{$currRow}", 'Major');
                    $sheet->setCellValue("J{$currRow}", 'High');
                    $sheet->setCellValue("K{$currRow}", 'SIT');
                    $sheet->setCellValue("L{$currRow}", 'Chrome / Win 11');
                    $sheet->setCellValue("M{$currRow}", '-');
                    $sheet->setCellValue("N{$currRow}", 'TC-001');
                    $sheet->setCellValue("O{$currRow}", $this->report->createdBy->name ?? 'QA');
                    $sheet->setCellValue("P{$currRow}", 'Backend Dev');
                    $sheet->setCellValue("Q{$currRow}", 'Open');
                    $sheet->setCellValue("R{$currRow}", '-');
                    $sheet->setCellValue("S{$currRow}", 'Contoh Bug - Belum ada bug tercatat');

                    $sheet->getStyle("A{$currRow}:S{$currRow}")->applyFromArray([
                        'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    ]);
                    $currRow++;
                } else {
                    foreach ($bugs as $b) {
                        $sheet->setCellValue("A{$currRow}", $b->bug_id);
                        $sheet->setCellValue("B{$currRow}", $b->found_date ? $b->found_date->format('d/m/Y') : '');
                        $sheet->setCellValue("C{$currRow}", $b->module_feature);
                        $sheet->setCellValue("D{$currRow}", $b->bug_type);
                        $sheet->setCellValue("E{$currRow}", $b->title);
                        $sheet->setCellValue("F{$currRow}", $b->reproduction_steps);
                        $sheet->setCellValue("G{$currRow}", $b->expected_result);
                        $sheet->setCellValue("H{$currRow}", $b->actual_result);
                        $sheet->setCellValue("I{$currRow}", $b->severity);
                        $sheet->setCellValue("J{$currRow}", $b->priority);
                        $sheet->setCellValue("K{$currRow}", $b->environment);
                        $sheet->setCellValue("L{$currRow}", $b->device_unit_id ?? '');
                        $sheet->setCellValue("M{$currRow}", $b->evidence_url ?? '');
                        $sheet->setCellValue("N{$currRow}", $b->related_test_case_id ?? '');
                        $sheet->setCellValue("O{$currRow}", $b->reported_by);
                        $sheet->setCellValue("P{$currRow}", $b->assigned_to ?? '');
                        $sheet->setCellValue("Q{$currRow}", $b->status);
                        $sheet->setCellValue("R{$currRow}", $b->fixed_date ? $b->fixed_date->format('d/m/Y') : '');
                        $sheet->setCellValue("S{$currRow}", $b->qa_retest_notes ?? '');

                        // Status Color Styling
                        $stColor = match($b->status) {
                            'Closed'          => 'DCFCE7', // soft green
                            'Open', 'Reopen'  => 'FEE2E2', // soft red
                            'In Progress'     => 'FEF3C7', // soft yellow
                            'Fixed', 'Ready to Retest' => 'E0F2FE', // soft blue
                            default           => 'F1F5F9',
                        };
                        $sheet->getStyle("Q{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($stColor);
                        $sheet->getStyle("Q{$currRow}")->getFont()->setBold(true);

                        $sheet->getStyle("A{$currRow}:S{$currRow}")->applyFromArray([
                            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                        ]);

                        $currRow++;
                    }
                }

                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(14);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(35);
                $sheet->getColumnDimension('G')->setWidth(28);
                $sheet->getColumnDimension('H')->setWidth(28);
                $sheet->getColumnDimension('I')->setWidth(14);
                $sheet->getColumnDimension('J')->setWidth(14);
                $sheet->getColumnDimension('N')->setWidth(16);
            },
        ];
    }
}
