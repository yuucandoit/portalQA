<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QaReportExport implements Export, WithMultipleSheets
{
    use Exportable;

    private ?int $reportId;

    public function __construct(?int $reportId = null)
    {
        $this->reportId = $reportId;
    }

    /**
     * Generate 7 distinct sheets for the 7 QA testing types.
     *
     * @return array
     */
    public function sheets(): array
    {
        $testTypes = [
            'functional',
            'integration',
            'regression',
            'performance',
            'reliability',
            'stress',
            'negative',
            'validation',
            'hardware',
        ];

        $sheets = [];
        foreach ($testTypes as $type) {
            $sheets[] = new QaTypeSheetExport($type, $this->reportId);
        }

        return $sheets;
    }
}
