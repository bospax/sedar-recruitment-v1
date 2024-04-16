<?php

namespace App\Http\Controllers;

use App\Exports\BanksExportError;
use App\Http\Requests\BanksImportRequest;
use App\Imports\BanksImport;

class BanksImportController extends Controller
{
    function store(BanksImportRequest $request)
    {
        $file = $request->file('import_bank');

        if ($request->hasFile('import_bank')) {
            $import = new BanksImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'banks-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['bank_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new BanksExportError($failed_rows);
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
