<?php

namespace App\Http\Controllers;

use App\Exports\FileTypesExportError;
use App\Http\Requests\FileTypesImportRequest;
use App\Imports\FileTypesImport;

class FileTypesImportController extends Controller
{
    function store(FileTypesImportRequest $request)
    {
        $file = $request->file('import_filetype');

        if ($request->hasFile('import_filetype')) {
            $import = new FileTypesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'filetypes-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['filetype_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new FileTypesExportError($failed_rows);
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
