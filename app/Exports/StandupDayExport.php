<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StandupDayExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly Collection $standups,
        private readonly string $date,
    ) {}

    public function sheets(): array
    {
        return [
            new StandupDaySummarySheet($this->standups, $this->date),
            new StandupDayItemsSheet($this->standups),
        ];
    }
}
