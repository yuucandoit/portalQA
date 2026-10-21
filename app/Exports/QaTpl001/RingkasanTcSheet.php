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

class RingkasanTcSheet implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    private TestReport $report;
    private array      $qg;

    public function __construct(TestReport $report)
    {
        $this->report = $report;
        $this->qg     = $report->getQualityGateMetrics();
    }

    public function title(): string
    {
        return 'Ringkasan_TC';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── Header Banner ─────────────────────────────────────────────
                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', 'RINGKASAN HASIL TEST (QA-TPL-001 v0.2)');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:D2');
                $sheet->setCellValue('A2', "Project: {$this->report->project->name} ({$this->report->project->project_code}) | Modul: {$this->report->report_name} | Tanggal: " . ($this->report->report_date ? $this->report->report_date->format('d/m/Y') : '-'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Table 1: Status Breakdown ─────────────────────────────────
                $headers = ['Status', 'Software', 'Hardware', 'Total'];
                foreach ($headers as $idx => $h) {
                    $col = chr(65 + $idx);
                    $sheet->setCellValue("{$col}4", $h);
                }
                $sheet->getRowDimension(4)->setRowHeight(24);
                $sheet->getStyle('A4:D4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);

                $isHw = $this->report->test_type === 'hardware';
                $swPass = $isHw ? 0 : $this->qg['pass_count'];
                $hwPass = $isHw ? $this->qg['pass_count'] : 0;
                $swFail = $isHw ? 0 : $this->qg['fail_count'];
                $hwFail = $isHw ? $this->qg['fail_count'] : 0;
                $swBlock = $isHw ? 0 : $this->qg['blocked_count'];
                $hwBlock = $isHw ? $this->qg['blocked_count'] : 0;
                $swNotRun = $isHw ? 0 : $this->qg['not_run_count'];
                $hwNotRun = $isHw ? $this->qg['not_run_count'] : 0;
                $swNa = $isHw ? 0 : $this->qg['na_count'];
                $hwNa = $isHw ? $this->qg['na_count'] : 0;

                $rows = [
                    ['Not Run', $swNotRun, $hwNotRun, '=B5+C5'],
                    ['Pass', $swPass, $hwPass, '=B6+C6'],
                    ['Fail', $swFail, $hwFail, '=B7+C7'],
                    ['Blocked', $swBlock, $hwBlock, '=B8+C8'],
                    ['N/A', $swNa, $hwNa, '=B9+C9'],
                    ['Total Test Case', '=SUM(B5:B9)', '=SUM(C5:C9)', '=SUM(D5:D9)'],
                    ['Executed (Pass + Fail)', '=B6+B7', '=C6+C7', '=D6+D7'],
                    ['Pass Rate (Pass / Executed)', '=IF(B11=0,0,B6/B11)', '=IF(C11=0,0,C6/C11)', '=IF(D11=0,0,D6/D11)'],
                    ['Progress Eksekusi ((Total - Not Run) / Total)', '=IF(B10=0,0,(B10-B5)/B10)', '=IF(C10=0,0,(C10-C5)/C10)', '=IF(D10=0,0,(D10-D5)/D10)'],
                ];

                $r = 5;
                foreach ($rows as $row) {
                    $sheet->setCellValue("A{$r}", $row[0]);
                    $sheet->setCellValue("B{$r}", $row[1]);
                    $sheet->setCellValue("C{$r}", $row[2]);
                    $sheet->setCellValue("D{$r}", $row[3]);

                    if ($r === 10 || $r === 11) {
                        $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true);
                    }
                    if ($r === 12 || $r === 13) {
                        $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true);
                        $sheet->getStyle("B{$r}:D{$r}")->getNumberFormat()->setFormatCode('0.0%');
                    }

                    $sheet->getStyle("B{$r}:D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $r++;
                }

                $sheet->getStyle('A5:D13')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);

                // ── Table 2: Completeness Check & Quality Gate ────────────────
                $sheet->mergeCells('A15:D15');
                $sheet->setCellValue('A15', 'CEK KELENGKAPAN & QUALITY GATE (Target = 0 Defisiensi)');
                $sheet->getRowDimension(15)->setRowHeight(24);
                $sheet->getStyle('A15')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->setCellValue('A16', 'TC Fail tanpa Bug ID (Aturan 3)');
                $sheet->setCellValue('B16', $isHw ? 0 : $this->qg['fail_without_bug']);
                $sheet->setCellValue('C16', $isHw ? $this->qg['fail_without_bug'] : 0);
                $sheet->setCellValue('D16', '=B16+C16');

                $sheet->setCellValue('A17', 'TC Pass/Fail tanpa Bukti (Aturan 4)');
                $sheet->setCellValue('B17', $isHw ? 0 : $this->qg['executed_without_evidence']);
                $sheet->setCellValue('C17', $isHw ? $this->qg['executed_without_evidence'] : 0);
                $sheet->setCellValue('D17', '=B17+C17');

                $sheet->setCellValue('A18', 'TC Hardware Pass tanpa Batas Lulus (Aturan 7)');
                $sheet->setCellValue('B18', 0);
                $sheet->setCellValue('C18', $this->qg['hw_pass_without_spec']);
                $sheet->setCellValue('D18', '=B18+C18');

                $sheet->setCellValue('A19', 'Active Critical/Major Bugs');
                $sheet->setCellValue('B19', $this->qg['active_critical_bugs']);
                $sheet->setCellValue('C19', $this->qg['active_critical_bugs']);
                $sheet->setCellValue('D19', $this->qg['active_critical_bugs']);

                $sheet->mergeCells('A20:B20');
                $sheet->setCellValue('A20', 'STATUS QUALITY GATE RILIS');
                $sheet->mergeCells('C20:D20');
                $sheet->setCellValue('C20', $this->qg['verdict']);

                $sheet->getStyle('A16:D20')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
                $sheet->getStyle('B16:D19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $qgColor = match($this->qg['verdict']) {
                    'GO'          => '10B981',
                    'CONDITIONAL' => 'F59E0B',
                    default       => 'EF4444',
                };

                $sheet->getStyle('A20:D20')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $qgColor]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(20)->setRowHeight(28);

                $sheet->getColumnDimension('A')->setWidth(45);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(18);
            },
        ];
    }
}
