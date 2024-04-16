<?php

namespace App\Http\Controllers;

use App\Exports\DivisionCategoriesExportError;
use App\Http\Requests\DivisionCategoriesImportRequest;
use App\Imports\DivisionCategoriesImport;

class DivisionCategoriesImportController extends Controller
{
    function store(DivisionCategoriesImportRequest $request)
    {
        $file = $request->file('import_division_category');

        if ($request->hasFile('import_division_category')) {
            $import = new DivisionCategoriesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'division_categories-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['category_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new DivisionCategoriesExportError($failed_rows);
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
