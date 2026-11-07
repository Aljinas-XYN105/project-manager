<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StandupDaySummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private readonly Collection $standups,
        private readonly string $date,
    ) {}

    public function title(): string { return 'Summary'; }

    public function headings(): array
    {
        return ['Date', 'Employee', 'Job Title', 'Items', 'Done', 'In Progress', 'Blockers'];
    }

    public function collection(): Collection
    {
        return $this->standups->map(fn ($s) => [
            $this->date,
            $s->user->name,
            $s->user->job_title ?? '',
            $s->items->count(),
            $s->items->where('status', 'done')->count(),
            $s->items->whereIn('status', ['in_progress', 'in_review'])->count(),
            $s->blockers ?? '',
        ]);
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 24, 'C' => 24, 'D' => 10, 'E' => 10, 'F' => 14, 'G' => 50];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7C3AED']],
            ],
        ];
    }
}
