<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StandupRangeExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly Collection $standups,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function sheets(): array
    {
        return [
            new StandupRangeSummarySheet($this->standups, $this->from, $this->to),
            new StandupRangeItemsSheet($this->standups),
        ];
    }
}
