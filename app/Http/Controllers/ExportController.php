<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Download exported CSV file
     */
    public function download(Request $request, $filename)
    {
        $filePath = 'exports/' . $filename;

        // Check if file exists
        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'File not found');
        }

        // Get file path
        $fullPath = Storage::disk('public')->path($filePath);

        // Return file as download with proper headers
        return response()->download($fullPath, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }
}
