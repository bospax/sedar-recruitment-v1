<?php

namespace App\Http\Controllers;

use App\Exports\SubunitsExportError;
use App\Http\Requests\SubunitsImportRequest;
use App\Imports\SubunitsImport;

class SubunitsImportController extends Controller
{
    function store(SubunitsImportRequest $request)
    {
        $file = $request->file('import_subunit');

        if ($request->hasFile('import_subunit')) {
            $import = new SubunitsImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'subunits-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['department'],
                        $failed_row['subunit'],
                        $failed_row['message']
                    ];
                }

                $export_error = new SubunitsExportError($failed_rows);
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
