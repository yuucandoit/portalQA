<?php

namespace App\Exports;

use App\Models\TestCase;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class QaTypeSheetExport implements Export, WithTitle, WithEvents, ShouldAutoSize
{
    use Exportable;

    private string $testType;
    private ?int   $reportId;

    public function __construct(string $testType, ?int $reportId = null)
    {
        $this->testType = $testType;
        $this->reportId = $reportId;
    }

    /**
     * Set sheet tab title.
     */
    public function title(): string
    {
        return match ($this->testType) {
            'functional'  => 'Functional Test',
            'integration' => 'Integration Test',
            'regression'  => 'Regression Test',
            'performance' => 'Performance Test',
            'reliability' => 'Reliability Test',
            'stress'      => 'Stress Test',
            'negative'    => 'Negative Test',
            'validation'  => 'Validation Test',
            'hardware'    => 'Hardware Test',
            default       => ucfirst($this->testType) . ' Test',
        };
    }

    /**
     * Get dynamic column titles (Column 3 & 4) based on test type.
     */
    private function getDynamicHeaderTitles(): array
    {
        return match ($this->testType) {
            'functional', 'negative' => ['Module / Fitur', 'Test Data'],
            'integration'            => ['Integration Point', 'Dependencies'],
            'regression'             => ['Related Feature', 'Reason / Linked Test Case'],
            'performance', 'stress'  => ['Test Type', 'Test Parameters'],
            'reliability'            => ['Test Type', 'Duration / Failure Rate'],
            'validation'             => ['Validation Type', 'Validation Criteria'],
            'hardware'               => ['Perangkat / Hardware', 'Environment / Parameter Uji'],
            default                  => ['Field 1', 'Field 2'],
        };
    }

    /**
     * Extract values for dynamic columns from JSON dynamic_fields.
     */
    private function getDynamicFieldValues(TestCase $testCase): array
    {
        $fields = $testCase->dynamic_fields ?? [];
        if (!is_array($fields)) {
            $fields = json_decode($fields, true) ?? [];
        }

        return match ($this->testType) {
            'functional'  => [$fields['moduleFeature'] ?? '', $fields['testData'] ?? ''],
            'negative'    => [$fields['moduleFeatureNeg'] ?? $fields['moduleFeature'] ?? '', $fields['testDataNeg'] ?? $fields['testData'] ?? ''],
            'integration' => [$fields['integrationPoint'] ?? '', $fields['dependencies'] ?? ''],
            'regression'  => [$fields['relatedFeature'] ?? '', $fields['reasonLinked'] ?? ''],
            'performance' => [$fields['testTypePerf'] ?? '', $fields['testParams'] ?? ''],
            'stress'      => [$fields['testTypeStress'] ?? '', $fields['stressParams'] ?? ''],
            'reliability' => [$fields['testTypeRel'] ?? '', $fields['durationFailure'] ?? ''],
            'validation'  => [$fields['validationType'] ?? '', $fields['validationCriteria'] ?? ''],
            'hardware'    => [$fields['deviceTested'] ?? '', $fields['environmentSpec'] ?? ''],
            default       => ['', ''],
        };
    }

    /**
     * Register events to build sheet headers, insert data, merge cells, and format styles.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── 1. Header Banner (Row 1) ──────────────────────────────────
                $sheetNameUpper = strtoupper($this->title());
                $sheet->mergeCells('A1:N1');
                $sheet->setCellValue('A1', "TEMPLATE TESTING REPORT QA - {$sheetNameUpper}");
                $sheet->getRowDimension(1)->setRowHeight(40);

                $sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'], // Dark Navy Slate
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // ── 2. Table Headers (Row 4) ──────────────────────────────────
                [$dynCol1, $dynCol2] = $this->getDynamicHeaderTitles();
                $headers = [
                    'No',
                    'Title',
                    $dynCol1,
                    $dynCol2,
                    'Priority',
                    'Precondition',
                    'Test Steps / Checklist',
                    'Expected Result',
                    'Actual Result',
                    'Status',
                    'Notes QA',
                    'Notes Programmer',
                    'Evidence',
                    'Test Result',
                ];

                $sheet->getRowDimension(4)->setRowHeight(28);
                foreach ($headers as $colIdx => $headerText) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                    $cellCoordinate = "{$colLetter}4";
                    $sheet->setCellValue($cellCoordinate, $headerText);
                }

                $sheet->getStyle('A4:N4')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 11,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2563EB'], // Royal Blue
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '93C5FD'],
                        ],
                    ],
                ]);

                // ── 3. Query & Insert Data (Row 5+) ──────────────────────────
                $query = TestCase::with(['steps', 'project', 'testedBy'])
                    ->where('test_type', $this->testType)
                    ->orderBy('created_at', 'desc');

                if ($this->reportId) {
                    $query->where('report_id', $this->reportId);
                }

                $testCases = $query->get();

                $currentRow = 5;
                $caseNumber = 1;

                foreach ($testCases as $testCase) {
                    $steps = $testCase->steps;
                    $stepCount = max(1, $steps->count());
                    $startRow  = $currentRow;
                    $endRow    = $startRow + $stepCount - 1;

                    [$dynVal1, $dynVal2] = $this->getDynamicFieldValues($testCase);
                    $testResult = match ($testCase->status) {
                        'PASS'   => 'PASS',
                        'FAILED' => 'FAIL',
                        default  => 'BLOCKED',
                    };

                    for ($i = 0; $i < $stepCount; $i++) {
                        $step = $steps->get($i);

                        if ($i === 0) {
                            // First row of Test Case with full metadata
                            $sheet->setCellValue("A{$currentRow}", $caseNumber);
                            $sheet->setCellValue("B{$currentRow}", $testCase->title);
                            $sheet->setCellValue("C{$currentRow}", $dynVal1);
                            $sheet->setCellValue("D{$currentRow}", $dynVal2);
                            $sheet->setCellValue("E{$currentRow}", $testCase->priority ?? 'Medium');
                            $sheet->setCellValue("F{$currentRow}", $testCase->precondition ?? '');
                            $sheet->setCellValue("G{$currentRow}", $step ? $step->step_description : 'Lakukan pengujian');
                            $sheet->setCellValue("H{$currentRow}", $step ? $step->expected_result : '');
                            $sheet->setCellValue("I{$currentRow}", $testCase->actual_result ?? '');
                            $sheet->setCellValue("J{$currentRow}", $testCase->status ?? 'PASS');
                            $sheet->setCellValue("K{$currentRow}", $testCase->notes_qa ?? '');
                            $sheet->setCellValue("L{$currentRow}", $testCase->notes_programmer ?? '');
                            $sheet->setCellValue("M{$currentRow}", $testCase->evidence_url ?? '');
                            $sheet->setCellValue("N{$currentRow}", $testResult);
                        } else {
                            // Subsequent sub-step rows
                            $sheet->setCellValue("A{$currentRow}", '');
                            $sheet->setCellValue("B{$currentRow}", '');
                            $sheet->setCellValue("C{$currentRow}", '');
                            $sheet->setCellValue("D{$currentRow}", '');
                            $sheet->setCellValue("E{$currentRow}", '');
                            $sheet->setCellValue("F{$currentRow}", '');
                            $sheet->setCellValue("G{$currentRow}", $step ? $step->step_description : '');
                            $sheet->setCellValue("H{$currentRow}", $step ? $step->expected_result : '');
                            $sheet->setCellValue("I{$currentRow}", '');
                            $sheet->setCellValue("J{$currentRow}", '');
                            $sheet->setCellValue("K{$currentRow}", '');
                            $sheet->setCellValue("L{$currentRow}", '');
                            $sheet->setCellValue("M{$currentRow}", '');
                            $sheet->setCellValue("N{$currentRow}", '');
                        }

                        $sheet->getRowDimension($currentRow)->setRowHeight(24);
                        $currentRow++;
                    }

                    // Merge metadata cells if multiple steps exist
                    if ($stepCount > 1) {
                        $metaCols = ['A', 'B', 'C', 'D', 'E', 'F', 'I', 'J', 'K', 'L', 'M', 'N'];
                        foreach ($metaCols as $col) {
                            $sheet->mergeCells("{$col}{$startRow}:{$col}{$endRow}");
                        }
                    }

                    // Format test case block rows
                    $blockRange = "A{$startRow}:N{$endRow}";

                    // Apply borders & alignment to block
                    $sheet->getStyle($blockRange)->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    // Center align No, Priority, Status, Test Result
                    $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$startRow}:E{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("J{$startRow}:J{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("N{$startRow}:N{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Style Status and Test Result badges
                    $statusUpper = strtoupper((string)$testCase->status);
                    $statusBg = match ($statusUpper) {
                        'PASS'            => 'DCFCE7', // Light green
                        'FAIL', 'FAILED'  => 'FEE2E2', // Light red
                        'BLOCKED'         => 'FEF3C7', // Light yellow
                        'NOT RUN'         => 'F1F5F9', // Light gray
                        'N/A'             => 'F8FAFC', // Slate
                        default           => 'FEF3C7',
                    };
                    $statusFg = match ($statusUpper) {
                        'PASS'            => '166534',
                        'FAIL', 'FAILED'  => '991B1B',
                        'BLOCKED'         => '92400E',
                        'NOT RUN'         => '475569',
                        'N/A'             => '64748B',
                        default           => '92400E',
                    };

                    $sheet->getStyle("J{$startRow}:J{$endRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $statusFg]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusBg]],
                    ]);

                    $sheet->getStyle("N{$startRow}:N{$endRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $statusFg]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusBg]],
                    ]);

                    $caseNumber++;
                }

                // If no test cases found, add placeholder template row
                if ($testCases->isEmpty()) {
                    $sheet->setCellValue('A5', 1);
                    $sheet->setCellValue('B5', '[Contoh] Validasi Fitur Utama');
                    $sheet->setCellValue('C5', $dynCol1);
                    $sheet->setCellValue('D5', $dynCol2);
                    $sheet->setCellValue('E5', 'Medium');
                    $sheet->setCellValue('F5', 'Sistem sudah berjalan normal');
                    $sheet->setCellValue('G5', '1. Buka menu utama');
                    $sheet->setCellValue('H5', '1. Halaman utama terbuka tanpa error');
                    $sheet->setCellValue('I5', 'Berhasil terbuka');
                    $sheet->setCellValue('J5', 'PASS');
                    $sheet->setCellValue('K5', '—');
                    $sheet->setCellValue('L5', '—');
                    $sheet->setCellValue('M5', '—');
                    $sheet->setCellValue('N5', 'PASS');

                    $sheet->getStyle('A5:N5')->applyFromArray([
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);
                    $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('J5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('N5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Explicit column width adjustments for clean layout
                $columnWidths = [
                    'A' => 6,   // No
                    'B' => 28,  // Title
                    'C' => 22,  // Dynamic 1
                    'D' => 22,  // Dynamic 2
                    'E' => 12,  // Priority
                    'F' => 26,  // Precondition
                    'G' => 32,  // Test Steps
                    'H' => 32,  // Expected Result
                    'I' => 28,  // Actual Result
                    'J' => 14,  // Status
                    'K' => 22,  // Notes QA
                    'L' => 22,  // Notes Programmer
                    'M' => 20,  // Evidence
                    'N' => 14,  // Test Result
                ];

                foreach ($columnWidths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
