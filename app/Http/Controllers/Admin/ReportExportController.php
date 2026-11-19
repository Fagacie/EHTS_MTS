<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Report\ReportsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, ReportsService $reports, string $dataset): StreamedResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $export = $reports->buildCsvDataset(
            dataset: $dataset,
            fromDate: is_string($from) ? $from : null,
            toDate: is_string($to) ? $to : null,
        );

        return response()->streamDownload(function () use ($export): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM improves Excel compatibility on Windows
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $export['headers']);

            foreach ($export['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $export['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
