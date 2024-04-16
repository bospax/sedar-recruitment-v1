<?php

namespace App\Http\Controllers;

use App\Exports\UsersExportError;
use App\Http\Requests\UsersImportRequest;
use App\Imports\UsersImport;

class UsersImportController extends Controller
{
    function store(UsersImportRequest $request)
    {
        $file = $request->file('import_user');

        if ($request->hasFile('import_user')) {
            $import = new UsersImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'users-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new UsersExportError($failed_rows);
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
