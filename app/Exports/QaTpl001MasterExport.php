<?php

namespace App\Exports;

use App\Models\TestReport;
use App\Exports\QaTpl001\PanduanSheet;
use App\Exports\QaTpl001\RingkasanTcSheet;
use App\Exports\QaTpl001\TcSoftwareSheet;
use App\Exports\QaTpl001\TcHardwareSheet;
use App\Exports\QaTpl001\LibraryParameterHwSheet;
use App\Exports\QaTpl001\BugListSheet;
use App\Exports\QaTpl001\SummaryBugSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QaTpl001MasterExport implements Export, WithMultipleSheets
{
    use Exportable;

    private TestReport $report;

    public function __construct(TestReport $report)
    {
        $this->report = $report;
    }

    /**
     * Generate the complete 7 sheets of QA-TPL-001 Master v0.2.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new PanduanSheet(),
            new RingkasanTcSheet($this->report),
            new TcSoftwareSheet($this->report),
            new TcHardwareSheet($this->report),
            new LibraryParameterHwSheet(),
            new BugListSheet($this->report),
            new SummaryBugSheet($this->report),
        ];
    }
}
