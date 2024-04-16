<?php

namespace App\Http\Controllers;

use App\Exports\CompaniesExportError;
use App\Http\Requests\CompaniesImportRequest;
use App\Imports\CompaniesImport;

class CompaniesImportController extends Controller
{
    function store(CompaniesImportRequest $request)
    {
        $file = $request->file('import_company');

        if ($request->hasFile('import_company')) {
            $import = new CompaniesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'companies-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['company_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new CompaniesExportError($failed_rows);
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
