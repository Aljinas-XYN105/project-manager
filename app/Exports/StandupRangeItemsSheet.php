<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StandupRangeItemsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(private readonly Collection $standups) {}

    public function title(): string { return 'Task Items'; }

    public function headings(): array
    {
        return ['Date', 'Employee', 'Task / Description', 'Status'];
    }

    public function collection(): Collection
    {
        $rows = collect();

        $statusLabels = [
            'todo'        => 'To Do',
            'in_progress' => 'In Progress',
            'in_review'   => 'In Review',
            'qa_testing'  => 'QA Testing',
            'done'        => 'Done',
        ];

        foreach ($this->standups as $standup) {
            $date = $standup->standup_date instanceof Carbon
                ? $standup->standup_date->format('Y-m-d')
                : Carbon::parse($standup->standup_date)->format('Y-m-d');

            foreach ($standup->items as $item) {
                $rows->push([
                    $date,
                    $standup->user->name,
                    $item->description,
                    $statusLabels[$item->status] ?? $item->status,
                ]);
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 24, 'C' => 60, 'D' => 16];
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
