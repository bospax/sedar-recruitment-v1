<?php

namespace App\Http\Controllers;

use App\Exports\LocationsExportError;
use App\Http\Requests\LocationsImportRequest;
use App\Imports\LocationsImport;

class LocationsImportController extends Controller
{
    function store(LocationsImportRequest $request)
    {
        $file = $request->file('import_location');

        if ($request->hasFile('import_location')) {
            $import = new LocationsImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'locations-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['location_name'],
                        $failed_row['message']
                    ];
                }

                $export_error = new LocationsExportError($failed_rows);
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
