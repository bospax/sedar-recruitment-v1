<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Requests\EmployeeAttainmentRequest;
use App\Http\Resources\EmployeeAttainment AS EmployeeAttainmentResources;
use App\Models\EmployeeAttainment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeAttainmentController extends Controller
{
    public function index() {
        $employee_attainments = DB::table('employee_attainments')
            ->leftJoin('employees', 'employee_attainments.employee_id', '=', 'employees.id')
            ->select([
                'employee_attainments.id',
                'employee_attainments.employee_id',
                'employee_attainments.attainment',
                'employee_attainments.course',
                'employee_attainments.degree',
                'employee_attainments.honorary',
                'employee_attainments.institution',
                'employee_attainments.attachment',
                'employee_attainments.years',
                'employee_attainments.gpa',
                'employee_attainments.academic_year_from',
                'employee_attainments.academic_year_to',
                'employee_attainments.attainment_remarks',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
            ])
            ->get();

        return EmployeeAttainmentResources::collection($employee_attainments);
    }

    public function show($id) {

    }

    public function store(EmployeeAttainmentRequest $request) {
        $employee_attainment = new EmployeeAttainment();

        $academic_year_from = $request->input('academic_year_from');
        $academic_year_to = $request->input('academic_year_to');
        $employee_attainment->employee_id = $request->input('employee_id');
        $employee_attainment->attainment = $request->input('attainment');
        $employee_attainment->course = $request->input('course');
        $employee_attainment->degree = $request->input('degree');
        $employee_attainment->honorary = $request->input('honorary');
        $employee_attainment->institution = $request->input('institution');
        $employee_attainment->years = $request->input('years');
        $employee_attainment->academic_year_from = $request->input('academic_year_from');
        $employee_attainment->academic_year_to = $request->input('academic_year_to');
        $employee_attainment->gpa = ($request->input('gpa')) ? $request->input('gpa') : '';
        $employee_attainment->attainment_remarks = $request->input('attainment_remarks');

        if ($academic_year_from != '' && $academic_year_to == '') {
            throw ValidationException::withMessages([
                'academic_year_to' => ['The \'to\' date is required.']
            ]);
        }

        if ($academic_year_from == '' && $academic_year_to != '') {
            throw ValidationException::withMessages([
                'academic_year_from' => ['The \'from\' date is required.']
            ]);
        }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;
            $filenames = [];

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/attainment_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $employee_attainment->attachment = implode(',', $filenames);
            }
        }

        if ($employee_attainment->save()) {
            Helpers::LogActivity($employee_attainment->id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - ATTAINMENT SECTION');
            return new EmployeeAttainmentResources($employee_attainment);
            // return response()->json($employee_attainment);
        }
    }

    public function update(EmployeeAttainmentRequest $request, $id) {
        $employee_attainment = EmployeeAttainment::findOrFail($id);

        $academic_year_from = $request->input('academic_year_from');
        $academic_year_to = $request->input('academic_year_to');
        $employee_attainment->employee_id = $request->input('employee_id');
        $employee_attainment->attainment = $request->input('attainment');
        $employee_attainment->course = $request->input('course');
        $employee_attainment->degree = $request->input('degree');
        $employee_attainment->honorary = $request->input('honorary');
        $employee_attainment->institution = $request->input('institution');
        $employee_attainment->years = $request->input('years');
        $employee_attainment->academic_year_from = $request->input('academic_year_from');
        $employee_attainment->academic_year_to = $request->input('academic_year_to');
        $employee_attainment->gpa = ($request->input('gpa')) ? $request->input('gpa') : '';
        $employee_attainment->attainment_remarks = $request->input('attainment_remarks');

        if ($academic_year_from != '' && $academic_year_to == '') {
            throw ValidationException::withMessages([
                'academic_year_to' => ['The \'to\' date is required.']
            ]);
        }

        if ($academic_year_from == '' && $academic_year_to != '') {
            throw ValidationException::withMessages([
                'academic_year_from' => ['The \'from\' date is required.']
            ]);
        }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;
            $filenames = [];

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/attainment_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $employee_attainment->attachment = implode(',', $filenames);
            }
        }

        if ($employee_attainment->save()) {
            Helpers::LogActivity($employee_attainment->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - ATTAINMENT SECTION');
            $employee_attainment = DB::table('employee_attainments')
                ->leftJoin('employees', 'employee_attainments.employee_id', '=', 'employees.id')
                ->select([
                    'employee_attainments.id',
                    'employee_attainments.employee_id',
                    'employee_attainments.attainment',
                    'employee_attainments.course',
                    'employee_attainments.degree',
                    'employee_attainments.honorary',
                    'employee_attainments.institution',
                    'employee_attainments.attachment',
                    'employee_attainments.years',
                    'employee_attainments.gpa',
                    'employee_attainments.academic_year_from',
                    'employee_attainments.academic_year_to',
                    'employee_attainments.attainment_remarks',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                ])
                ->where('employee_attainments.id', '=', $employee_attainment->id)
                ->get();

            return EmployeeAttainmentResources::collection($employee_attainment);
        }
    }

    public function destroy($id) {
        $employee_attainment = EmployeeAttainment::findOrFail($id);

        if ($employee_attainment->delete()) {
            Helpers::LogActivity($employee_attainment->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - ATTAINMENT SECTION');
            return new EmployeeAttainmentResources($employee_attainment);
        }
    }

    public function getEmployeeAttainment($id) {
        $employee_attainments = DB::table('employee_attainments')
            ->leftJoin('employees', 'employee_attainments.employee_id', '=', 'employees.id')
            ->select([
                'employee_attainments.id',
                'employee_attainments.employee_id',
                'employee_attainments.attainment',
                'employee_attainments.course',
                'employee_attainments.degree',
                'employee_attainments.honorary',
                'employee_attainments.institution',
                'employee_attainments.attachment',
                'employee_attainments.years',
                'employee_attainments.gpa',
                'employee_attainments.academic_year_from',
                'employee_attainments.academic_year_to',
                'employee_attainments.attainment_remarks',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
            ])
            ->where('employee_id', '=', $id)
            ->get();

        return EmployeeAttainmentResources::collection($employee_attainments);
    }
}
