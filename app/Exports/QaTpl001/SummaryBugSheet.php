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

class SummaryBugSheet implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    private TestReport $report;

    public function __construct(TestReport $report)
    {
        $this->report = $report;
    }

    public function title(): string
    {
        return 'Summary_Bug';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── 1. Header Banner (Row 1) ──────────────────────────────────
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'BUG SUMMARY & DEFECT METRICS (QA-TPL-001 v0.2)');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── 2. Matrix Severity vs Status (Rows 4-10) ──────────────────
                $headers = ['Severity \ Status', 'Open', 'In Progress', 'Fixed', 'Ready to Retest', 'Reopen', 'Closed', 'Rejected', 'Deferred', 'Total'];
                foreach ($headers as $idx => $h) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
                    $sheet->setCellValue("{$col}4", $h);
                }

                $sheet->getRowDimension(4)->setRowHeight(26);
                $sheet->getStyle('A4:J4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);

                // Query bugs for counts
                $tcIds = $this->report->testCases->pluck('test_case_id')->filter()->values()->all();
                $bugs = Bug::where('report_id', $this->report->id)
                    ->orWhere(function ($q) use ($tcIds) {
                        $q->where('project_id', $this->report->project_id);
                        if (!empty($tcIds)) {
                            $q->whereIn('related_test_case_id', $tcIds);
                        }
                    })
                    ->get();

                $severities = ['Critical', 'Major', 'Minor', 'Trivial'];
                $statuses   = ['Open', 'In Progress', 'Fixed', 'Ready to Retest', 'Reopen', 'Closed', 'Rejected', 'Deferred'];

                $rowIdx = 5;
                foreach ($severities as $sev) {
                    $sheet->setCellValue("A{$rowIdx}", $sev);
                    $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);

                    foreach ($statuses as $sIdx => $st) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($sIdx + 2);
                        $count = $bugs->filter(fn($b) => $b->severity === $sev && $b->status === $st)->count();
                        $sheet->setCellValue("{$col}{$rowIdx}", $count);
                        $sheet->getStyle("{$col}{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Total Column
                    $sheet->setCellValue("J{$rowIdx}", "=SUM(B{$rowIdx}:I{$rowIdx})");
                    $sheet->getStyle("J{$rowIdx}")->getFont()->setBold(true);
                    $sheet->getStyle("J{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $rowIdx++;
                }

                // Total Row (Row 9)
                $sheet->setCellValue("A9", "Total");
                $sheet->getStyle("A9")->getFont()->setBold(true);
                for ($c = 2; $c <= 10; $c++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $sheet->setCellValue("{$col}9", "=SUM({$col}5:{$col}8)");
                    $sheet->getStyle("{$col}9")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $sheet->getStyle('A5:J9')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);

                // ── 3. Table 2: Quality Indicators (Rows 11-16) ───────────────
                $sheet->setCellValue("A11", "Indikator Kualitas Defect");
                $sheet->setCellValue("B11", "Nilai");
                $sheet->getStyle('A11:B11')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $totalBugs     = $bugs->count();
                $activeBugs    = $bugs->whereIn('status', ['Open', 'In Progress', 'Fixed', 'Ready to Retest', 'Reopen'])->count();
                $critMajorAct  = $bugs->whereIn('severity', ['Critical', 'Major'])->whereNotIn('status', ['Closed', 'Rejected', 'Deferred'])->count();
                $closedBugs    = $bugs->where('status', 'Closed')->count();

                $indicators = [
                    ['Total bug tercatat', $totalBugs],
                    ['Bug aktif (Open + In Progress + Reopen + Ready to Retest + Fixed)', $activeBugs],
                    ['Critical + Major masih aktif (Blocker Rilis)', $critMajorAct],
                    ['Bug Closed', $closedBugs],
                ];

                $r = 12;
                foreach ($indicators as $ind) {
                    $sheet->setCellValue("A{$r}", $ind[0]);
                    $sheet->setCellValue("B{$r}", $ind[1]);
                    $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("B{$r}")->getFont()->setBold(true);
                    if ($r === 14 && $ind[1] > 0) {
                        $sheet->getStyle("B{$r}")->getFont()->getColor()->setRGB('DC2626');
                    }
                    $r++;
                }

                $sheet->getStyle('A11:B15')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);

                $sheet->getColumnDimension('A')->setWidth(40);
                for ($c = 2; $c <= 10; $c++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
            },
        ];
    }
}
