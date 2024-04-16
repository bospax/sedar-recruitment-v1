<?php

namespace App\Http\Controllers;

use App\Exports\PositionsExportError;
use App\Http\Requests\PositionsImportRequest;
use App\Imports\PositionsImport;

class PositionsImportController extends Controller
{
    function store(PositionsImportRequest $request)
    {
        $file = $request->file('import_position');

        if ($request->hasFile('import_position')) {
            $import = new PositionsImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'positions-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['code'],
                        $failed_row['position_name'],
                        $failed_row['department'],
                        $failed_row['subunit'],
                        // $failed_row['location'],
                        $failed_row['jobband'],
                        $failed_row['superior_id_prefix'],
                        $failed_row['superior_id_number'],
                        $failed_row['payrate'],
                        $failed_row['team'],
                        $failed_row['tools'],
                        $failed_row['message']
                    ];
                }

                $export_error = new PositionsExportError($failed_rows);
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
