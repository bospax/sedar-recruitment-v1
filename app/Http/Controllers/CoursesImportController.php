<?php

namespace App\Http\Controllers;

use App\Exports\CoursesExportError;
use App\Http\Requests\CoursesImportRequest;
use App\Imports\CoursesImport;

class CoursesImportController extends Controller
{
    function store(CoursesImportRequest $request)
    {
        $file = $request->file('import_course');

        if ($request->hasFile('import_course')) {
            $import = new CoursesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'courses-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['id'],
                        $failed_row['course_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new CoursesExportError($failed_rows);
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
