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

class StandupTasksSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(private readonly Collection $reports) {}

    public function title(): string
    {
        return 'Task Breakdown';
    }

    public function headings(): array
    {
        return ['Date', 'Employee', 'Project', 'Task', 'Description', 'Hours', 'Status', 'Blocked', 'Notes'];
    }

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->reports as $report) {
            foreach ($report->reportTasks as $rt) {
                $statusLabels = [
                    'in_progress' => 'In Progress',
                    'in_review'   => 'In Review',
                    'done'        => 'Done',
                    'blocked'     => 'Blocked',
                    'todo'        => 'To Do',
                    'qa_testing'  => 'QA Testing',
                ];

                $rows->push([
                    $report->report_date->format('Y-m-d'),
                    $report->user->name,
                    $rt->task?->project?->name ?? '',
                    $rt->task?->title ?? '',
                    $rt->task_description,
                    number_format($rt->hours_spent, 1),
                    $statusLabels[$rt->status_update ?? ''] ?? ($rt->status_update ?? ''),
                    $rt->is_blocked ? 'Yes' : 'No',
                    $rt->notes ?? '',
                ]);
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 22,
            'C' => 22,
            'D' => 30,
            'E' => 40,
            'F' => 10,
            'G' => 14,
            'H' => 10,
            'I' => 35,
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
