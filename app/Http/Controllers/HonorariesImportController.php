<?php

namespace App\Http\Controllers;

use App\Exports\HonorariesExportError;
use App\Http\Requests\HonorariesImportRequest;
use App\Imports\HonorariesImport;

class HonorariesImportController extends Controller
{
    function store(HonorariesImportRequest $request)
    {
        $file = $request->file('import_honorary');

        if ($request->hasFile('import_honorary')) {
            $import = new HonorariesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'honoraries-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['honorary_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new HonorariesExportError($failed_rows);
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
