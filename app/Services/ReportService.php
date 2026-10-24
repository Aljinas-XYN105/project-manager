<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    public function exportToPdf(string $view, array $data): string
    {
        $pdf  = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');
        $path = storage_path('app/exports/report_' . now()->format('Ymd_His') . '.pdf');

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);
        return $path;
    }
}
