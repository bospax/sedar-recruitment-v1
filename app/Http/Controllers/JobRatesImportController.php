<?php

namespace App\Http\Controllers;

use App\Exports\JobRatesExportError;
use App\Http\Requests\JobRatesImportRequest;
use App\Imports\JobRatesImport;

class JobRatesImportController extends Controller
{
    function store(JobRatesImportRequest $request)
    {
        $file = $request->file('import_jobrate');

        if ($request->hasFile('import_jobrate')) {
            $import = new JobRatesImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'jobrates-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['code'],
                        $failed_row['job_level'],
                        $failed_row['position'],
                        $failed_row['job_rate'],
                        $failed_row['allowance'],
                        $failed_row['salary_structure'],
                        $failed_row['description'],
                        $failed_row['message']
                    ];
                }

                $export_error = new JobRatesExportError($failed_rows);
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
