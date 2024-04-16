<?php

namespace App\Http\Controllers;

use App\Exports\TitlesExportError;
use App\Http\Requests\TitlesImportRequest;
use App\Imports\TitlesImport;

class TitlesImportController extends Controller
{
    function store(TitlesImportRequest $request)
    {
        $file = $request->file('import_title');

        if ($request->hasFile('import_title')) {
            $import = new TitlesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'titles-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['title_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new TitlesExportError($failed_rows);
                $export_error->store('public/files/'.$filename);
            }

            return response()->json([
                'status' => 'imported',
                'message' => 'successfully imported',
                'errors' => $failures,
                'link' => '/storage/files/'.$filename
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'file is required'
            ]);
        }
    }
}
