<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\EmployeeFile as ResourcesEmployeeFile;
use App\Models\EmployeeFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\Input;
use Illuminate\Validation\ValidationException;

class EmployeeFileController extends Controller
{
    public function index() {
        
    }

    public function show($id) {

    }

    public function store(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => 'required'
        ]);

        if (empty($request->input('files'))) {
            throw ValidationException::withMessages([
                'file_type' => ['Files are required.']
            ]);
        }

        $employee_id = $request->input('employee_id');
        $data = [];

        foreach ($request->input('files') as $key => $file) {
            $file = json_decode($file);
            
            if (empty($file->file_type)) {
                throw ValidationException::withMessages([
                    'file_type' => ['File type is required.']
                ]);
            }

            if (empty($file->cabinet_number)) {
                throw ValidationException::withMessages([
                    'cabinet_number' => ['Cabinet number is required.']
                ]);
            }

            // if (empty($file->description)) {
            //     throw ValidationException::withMessages([
            //         'description' => ['Description is required.']
            //     ]);
            // }
        }

        foreach ($request->input('files') as $key => $file) {
            $file = json_decode($file);
            $uploaded = $request->uploaded[$key];

            if ($uploaded) {
                $filenameWithExt = $uploaded->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $uploaded->storeAs('public/201_files', $fileNameToStore);
                $filename = str_replace(',', '', $fileNameToStore);

                $data[] = [
                    'employee_id' => $employee_id,
                    'file_type' => $file->file_type,
                    'cabinet_number' => $file->cabinet_number,
                    'description' => $file->description,
                    'file' => $filename,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            } else {
                $data[] = [
                    'employee_id' => $employee_id,
                    'file_type' => $file->file_type,
                    'cabinet_number' => $file->cabinet_number,
                    'description' => $file->description,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        Helpers::LogActivity($employee_id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - FILE SECTION');
        EmployeeFile::insert($data);
    }

    public function update(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => 'required'
        ]);

        if (empty($request->input('files'))) {
            throw ValidationException::withMessages([
                'file_type' => ['Files are required.']
            ]);
        }

        $employee_id = $request->input('employee_id');
        $data = [];
        $filename = '';

        foreach ($request->input('files') as $key => $file) {
            $file = json_decode($file);
            $uploaded = $request->uploaded[$key];

            if ($uploaded) {
                $filenameWithExt = $uploaded->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $uploaded->storeAs('public/201_files', $fileNameToStore);
                $filename = str_replace(',', '', $fileNameToStore);

                $data = [
                    'employee_id' => $employee_id,
                    'file_type' => $file->file_type,
                    'cabinet_number' => $file->cabinet_number,
                    'description' => $file->description,
                    'file' => $filename,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            } else {
                $data = [
                    'employee_id' => $employee_id,
                    'file_type' => $file->file_type,
                    'cabinet_number' => $file->cabinet_number,
                    'description' => $file->description,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        $update = DB::table('employee_files')
            ->where('id', $id)
            ->update($data);

        $updated = DB::table('employee_files')
            ->select([
                'id',
                'employee_id',
                'file_type',
                'cabinet_number',
                'description',
                'file'
            ])
            ->where('id', $id)
            ->get();
            
        Helpers::LogActivity($employee_id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - FILE SECTION');
        return ResourcesEmployeeFile::collection($updated);
    }

    public function destroy($id) {
        $file = EmployeeFile::findOrFail($id);

        if ($file->delete()) {
            Helpers::LogActivity($file->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - FILE SECTION');
            return new ResourcesEmployeeFile($file);
        }
    }

    public function getFiles($employee_id) {
        $files = DB::table('employee_files')
            ->select([
                'employee_files.id',
                'employee_files.employee_id',
                'employee_files.file_type',
                'employee_files.cabinet_number',
                'employee_files.description',
                'employee_files.file'
            ])
            ->where('employee_id', '=', $employee_id)
            ->get();

        return response()->json($files);
    }

    public function getFilesTable($employee_id) {
        $files = DB::table('employee_files')
            ->select([
                'employee_files.id',
                'employee_files.employee_id',
                'employee_files.file_type',
                'employee_files.cabinet_number',
                'employee_files.description',
                'employee_files.file'
            ])
            ->where('employee_id', '=', $employee_id)
            ->get();

        return ResourcesEmployeeFile::collection($files);
    }
}
