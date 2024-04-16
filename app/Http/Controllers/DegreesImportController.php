<?php

namespace App\Http\Controllers;

use App\Exports\DegreesExportError;
use App\Http\Requests\DegreesImportRequest;
use App\Imports\DegreesImport;

class DegreesImportController extends Controller
{
    function store(DegreesImportRequest $request)
    {
        $file = $request->file('import_degree');

        if ($request->hasFile('import_degree')) {
            $import = new DegreesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'degrees-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['degree_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new DegreesExportError($failed_rows);
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
