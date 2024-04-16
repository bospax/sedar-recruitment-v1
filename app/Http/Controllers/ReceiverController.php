<?php

namespace App\Http\Controllers;

use App\Http\Resources\Receiver as ResourcesReceiver;
use App\Models\Receiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiverController extends Controller
{
    public function index()
    {   
        $forms = DB::table('receivers')
        ->leftJoin('employees', 'receivers.employee_id', '=', 'employees.id')
        ->select([
            'receivers.id',
            'receivers.form_type',
            'receivers.batch',
            'receivers.label',
            'receivers.employee_id',
            'employees.prefix_id',
            'employees.id_number',
            'employees.first_name',
            'employees.middle_name',
            'employees.last_name',
            'employees.suffix',
            'employees.gender',
            'employees.image',
        ])
        ->groupBy('receivers.batch')
        ->get();

        return ResourcesReceiver::collection($forms);
    }

    public function store(Request $request)
    {
        $data = [];
        $form_type = $request->input('form_type');
        $label = $request->input('label');
        $subunits = $request->input('subunits');
        $employee_id = $request->input('employee_id');

        if (empty($form_type)) {
            throw ValidationException::withMessages([
                'form_type_receiver' => ['The form type field is required.']
            ]);
        }

        if (empty($label)) {
            throw ValidationException::withMessages([
                'label_receiver' => ['The label field is required.']
            ]);
        }

        if (empty($employee_id)) {
            throw ValidationException::withMessages([
                'employee_id_receiver' => ['The employee id field is required.']
            ]);
        }

        if (empty($employee_id)) {
            throw ValidationException::withMessages([
                'employee_id_receiver' => ['The employee id field is required.']
            ]);
        }

        if (empty($subunits)) {
            throw ValidationException::withMessages([
                'subunits_receiver' => ['Select atleast one subunit for the form.']
            ]);
        }

        $duplicate_label = DB::table('receivers')
            ->select(['id'])
            ->where('label', '=', $label)
            ->get();

        if ($duplicate_label->count()) {
            throw ValidationException::withMessages([
                'label_receiver' => ['The label has already been taken.']
            ]);
        }

        $last_row = DB::table('receivers')->latest('id')->first();
        $batch_no = (!empty($last_row)) ? $last_row->id + 1 : 1;

        foreach ($subunits as $subunit) {
            $data[] = [
                'form_type' => $form_type,
                'batch' => 'batch-'.$batch_no,
                'label' => $label,
                'subunit_id' => $subunit,
                'employee_id' => $employee_id
            ];
        }

        $receivers = Receiver::insert($data);
        return response()->json($receivers);
    }

    public function update(Request $request)
    {
        $data = [];
        $id = $request->input('id');
        $form_type = $request->input('form_type');
        $batch = $request->input('batch');
        $label = $request->input('label');
        $subunits = $request->input('subunits');
        $employee_id = $request->input('employee_id');

        if (empty($form_type)) {
            throw ValidationException::withMessages([
                'form_type_receiver' => ['The form type field is required.']
            ]);
        }

        if (empty($label)) {
            throw ValidationException::withMessages([
                'label_receiver' => ['The label field is required.']
            ]);
        }

        if (empty($employee_id)) {
            throw ValidationException::withMessages([
                'employee_id_receiver' => ['The employee id field is required.']
            ]);
        }

        if (empty($employee_id)) {
            throw ValidationException::withMessages([
                'employee_id_receiver' => ['The employee id field is required.']
            ]);
        }

        if (empty($subunits)) {
            throw ValidationException::withMessages([
                'subunits_receiver' => ['Select atleast one subunit for the form.']
            ]);
        }

        $duplicate_label = DB::table('receivers')
            ->select(['id'])
            ->where('label', '=', $label)
            ->where('batch', '!=', $batch)
            ->where('id', '!=', $id)
            ->get();

        if ($duplicate_label->count()) {
            throw ValidationException::withMessages([
                'label_receiver' => ['The label has already been taken.']
            ]);
        }

        $delete = Receiver::where('batch', $batch)->delete();

        if ($delete) {
            $last_row = DB::table('receivers')->latest('id')->first();
            $batch_no = (!empty($last_row)) ? $last_row->id + 1 : 1;

            foreach ($subunits as $subunit) {
                $data[] = [
                    'form_type' => $form_type,
                    'batch' => 'batch-'.$batch_no,
                    'label' => $label,
                    'subunit_id' => $subunit,
                    'employee_id' => $employee_id
                ];
            }

            $receivers = Receiver::insert($data);
            return response()->json($receivers);
        }
    }

    public function destroy($batch)
    {
        $delete = Receiver::where('batch', $batch)->delete();
    }
}
