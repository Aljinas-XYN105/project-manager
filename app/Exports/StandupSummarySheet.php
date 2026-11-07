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

class StandupSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(private readonly Collection $reports, private readonly string $date) {}

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        return ['Date', 'Employee', 'Job Title', 'Total Hours', 'Summary', 'Plan for Tomorrow', 'Blockers', 'Blocked Tasks'];
    }

    public function collection(): Collection
    {
        return $this->reports->map(fn($r) => [
            $r->report_date->format('Y-m-d'),
            $r->user->name,
            $r->user->job_title ?? '',
            number_format($r->total_hours, 1),
            $r->summary,
            $r->plan_for_tomorrow ?? '',
            $r->blockers ?? '',
            $r->reportTasks->where('is_blocked', true)->count(),
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 22,
            'C' => 22,
            'D' => 14,
            'E' => 50,
            'F' => 40,
            'G' => 40,
            'H' => 14,
        ];
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
