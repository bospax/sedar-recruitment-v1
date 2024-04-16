<?php

namespace App\Http\Controllers;

use App\Exports\DivisionsExportError;
use App\Http\Requests\DivisionsImportRequest;
use App\Imports\DivisionsImport;

class DivisionsImportController extends Controller
{
    function store(DivisionsImportRequest $request)
    {
        $file = $request->file('import_division');

        if ($request->hasFile('import_division')) {
            $import = new DivisionsImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'divisions-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['division_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new DivisionsExportError($failed_rows);
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
