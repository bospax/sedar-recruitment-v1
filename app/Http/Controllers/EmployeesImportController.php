<?php

namespace App\Http\Controllers;

use App\Exports\EmployeesExportError;
use App\Http\Requests\EmployeesImportRequest;
use App\Imports\EmployeesImport;

class EmployeesImportController extends Controller
{
    function store(EmployeesImportRequest $request)
    {
        $file = $request->file('import_employee');

        if ($request->hasFile('import_employee')) {
            $import = new EmployeesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'employees-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['first_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new EmployeesExportError($failed_rows);
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
