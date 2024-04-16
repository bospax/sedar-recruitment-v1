<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentsExportError;
use App\Http\Requests\DepartmentsImportRequest;
use App\Imports\DepartmentsImport;

class DepartmentsImportController extends Controller
{
    function store(DepartmentsImportRequest $request)
    {
        $file = $request->file('import_department');

        if ($request->hasFile('import_department')) {
            $import = new DepartmentsImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'departments-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['department'],
                        // $failed_row['division'],
                        // $failed_row['category'],
                        // $failed_row['company'],
                        // $failed_row['location'],
                        $failed_row['message']
                    ];
                }

                $export_error = new DepartmentsExportError($failed_rows);
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
