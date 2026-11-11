<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithStyles
{
    protected $report;
    protected $period;

    public function __construct(array $report, string $period)
    {
        $this->report = $report;
        $this->period = $period;
    }

    public function array(): array
    {
        return [
            ['Period', ucfirst($this->period)],
            ['Total Sales', number_format($this->report['sales'], 2)],
            ['Total Expenses', number_format($this->report['expenses'], 2)],
            ['Net Profit', number_format($this->report['profit'], 2)],
            ['Number of Transactions', $this->report['sales_count']],
        ];
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
