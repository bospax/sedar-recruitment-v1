<?php

namespace App\Http\Controllers;

use App\Exports\JobBandsExportError;
use App\Http\Requests\JobBandsImportRequest;
use App\Imports\JobBandsImport;

class JobBandsImportController extends Controller
{
    function store(JobBandsImportRequest $request)
    {
        $file = $request->file('import_jobband');

        if ($request->hasFile('import_jobband')) {
            $import = new JobBandsImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'jobbands-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['jobband_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new JobBandsExportError($failed_rows);
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
