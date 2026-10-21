<?php

namespace App\Exports\QaTpl001;

use App\Services\HardwareParameterLibrary;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LibraryParameterHwSheet implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    public function title(): string
    {
        return 'Library_Parameter_HW';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── 1. Header Banner (Row 1) ──────────────────────────────────
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'LIBRARY PARAMETER HARDWARE (QA-TPL-001 Master v0.2)');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                $sheet->mergeCells('A2:F2');
                $sheet->setCellValue('A2', 'Pustaka 33 parameter baku hardware QA terbagi dalam 9 kategori teknis. Digunakan sebagai acuan batas lulus kuantitatif.');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                ]);

                // ── 2. Table Column Headers (Row 4) ───────────────────────────
                $headers = [
                    'A' => 'No',
                    'B' => 'Kategori',
                    'C' => 'Parameter yang Diukur',
                    'D' => 'Satuan',
                    'E' => 'Cara Ukur / Alat',
                    'F' => 'Contoh Batas Lulus (Spec)',
                ];

                $sheet->getRowDimension(4)->setRowHeight(26);
                foreach ($headers as $col => $title) {
                    $sheet->setCellValue("{$col}4", $title);
                }

                $sheet->getStyle('A4:F4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
                ]);

                // ── 3. Populate 33 Parameters ─────────────────────────────────
                $params = HardwareParameterLibrary::all();
                $r = 5;
                foreach ($params as $idx => $p) {
                    $sheet->setCellValue("A{$r}", $idx + 1);
                    $sheet->setCellValue("B{$r}", $p['kategori']);
                    $sheet->setCellValue("C{$r}", $p['parameter']);
                    $sheet->setCellValue("D{$r}", $p['satuan']);
                    $sheet->setCellValue("E{$r}", $p['cara_ukur']);
                    $sheet->setCellValue("F{$r}", $p['batas_lulus']);

                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$r}")->getFont()->setBold(true);

                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    ]);

                    $r++;
                }

                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(24);
                $sheet->getColumnDimension('C')->setWidth(35);
                $sheet->getColumnDimension('D')->setWidth(14);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(45);
            },
        ];
    }
}
