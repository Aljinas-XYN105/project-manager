<?php

namespace App\Exports;

use App\Models\DailyReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StandupExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly Collection $reports,
        private readonly string $date,
    ) {}

    public function sheets(): array
    {
        return [
            new StandupSummarySheet($this->reports, $this->date),
            new StandupTasksSheet($this->reports),
        ];
    }
}
