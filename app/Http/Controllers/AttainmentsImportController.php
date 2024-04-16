<?php

namespace App\Http\Controllers;

use App\Exports\AttainmentsExportError;
use App\Http\Requests\AttainmentsImportRequest;
use App\Imports\AttainmentsImport;

class AttainmentsImportController extends Controller
{
    function store(AttainmentsImportRequest $request)
    {
        $file = $request->file('import_attainment');

        if ($request->hasFile('import_attainment')) {
            $import = new AttainmentsImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'attainments-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['attainment_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new AttainmentsExportError($failed_rows);
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
