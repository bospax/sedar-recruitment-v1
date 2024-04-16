<?php

namespace App\Http\Controllers;

use App\Exports\FormSettingExportError;
use App\Http\Resources\Approver as ApproverResources;
use App\Http\Resources\Form as ResourcesForm;
use App\Imports\FormSettingImport;
use App\Models\Form;
use App\Models\Receiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    public function index()
    {  
        $keyword = request('keyword');

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $forms = DB::table('forms')
                ->leftJoin('employees', 'forms.receiver_id', '=', 'employees.id')
                ->select([
                    'forms.id',
                    'forms.form_type',
                    'forms.batch',
                    'forms.label',
                    'forms.receiver_id',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                ])
                ->where('forms.label', 'LIKE', $value)
                ->groupBy('forms.batch')
                ->paginate(15);
        } else {
            $forms = DB::table('forms')
                ->leftJoin('employees', 'forms.receiver_id', '=', 'employees.id')
                ->select([
                    'forms.id',
                    'forms.form_type',
                    'forms.batch',
                    'forms.label',
                    'forms.receiver_id',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                ])
                ->groupBy('forms.batch')
                ->paginate(15);
        }

        return ResourcesForm::collection($forms);
    }

    public function store(Request $request)
    {
        $duplicate_label = DB::table('forms')
            ->select(['id'])
            ->where('label', '=', $request->input('label'))
            ->get();

        if ($duplicate_label->count()) {
            throw ValidationException::withMessages([
                'custom_label' => ['The label has already been taken.']
            ]);
        }

        $this->validate($request, [
            'form_type' => ['required'],
            'label' => ['required', 'unique:forms,label']
        ]);

        $data = [];
        $data_receiver = [];
        $form_type = $request->input('form_type');
        $label = $request->input('label');
        $subunits = $request->input('subunits');
        $approvers = $request->input('approvers');
        $receiver_id = $request->input('receiver_id');

        if (empty($approvers)) {
            throw ValidationException::withMessages([
                'approvers' => ['Select atleast one approver for the form.']
            ]);
        }

        if (empty($subunits)) {
            throw ValidationException::withMessages([
                'subunits' => ['Select atleast one subunit for the form.']
            ]);
        }

        $last_row = DB::table('forms')->latest('id')->first();
        $batch_no = (!empty($last_row)) ? $last_row->id + 1 : 1;

        foreach ($subunits as $subunit) {
            foreach ($approvers as $approver) {
                $data[] = [
                    'form_type' => $form_type,
                    'batch' => 'batch-'.$batch_no,
                    'label' => $label,
                    'subunit_id' => $subunit,
                    'employee_id' => $approver['employee_id'],
                    'action' => $approver['action'],
                    'level' => $approver['level'],
                    'receiver_id' => $receiver_id
                ];
            }
        }

        foreach ($subunits as $subunit) {
            $data_receiver[] = [
                'form_type' => $form_type,
                'batch' => 'batch-'.$batch_no,
                'label' => $label,
                'subunit_id' => $subunit,
                'employee_id' => $receiver_id
            ];
        }

        $receivers = Receiver::insert($data_receiver);
        $forms = Form::insert($data);
        return response()->json($forms);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'form_type' => ['required'],
            'label' => ['required']
        ]);

        $data = [];
        $form_type = $request->input('form_type');
        $label = $request->input('label');
        $batch = $request->input('batch');
        $subunits = $request->input('subunits');
        $approvers = $request->input('approvers');
        $receiver_id = $request->input('receiver_id');

        $duplicate = DB::table('forms')
            ->select('id')
            ->where('batch', '!=', $batch)
            ->where('label', '=', $label)
            ->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                'custom_label' => ['The label has already been taken.']
            ]);
        }

        if (empty($approvers)) {
            throw ValidationException::withMessages([
                'approvers' => ['Select atleast one approver for the form.']
            ]);
        }

        if (empty($subunits)) {
            throw ValidationException::withMessages([
                'subunits' => ['Select atleast one subunit for the form.']
            ]);
        }

        $delete = Form::where('batch', $batch)->delete();
        $delete_receiver = Receiver::where('batch', $batch)->delete();

        if ($delete) {
            $last_row = DB::table('forms')->latest('id')->first();
            $batch_no = (!empty($last_row)) ? $last_row->id + 1 : 1;

            foreach ($subunits as $subunit) {
                foreach ($approvers as $approver) {
                    $data[] = [
                        'form_type' => $form_type,
                        'batch' => 'batch-'.$batch_no,
                        'label' => $label,
                        'subunit_id' => $subunit,
                        'employee_id' => $approver['employee_id'],
                        'action' => $approver['action'],
                        'level' => $approver['level'],
                        'receiver_id' => $receiver_id
                    ];
                }
            }

            foreach ($subunits as $subunit) {
                $data_receiver[] = [
                    'form_type' => $form_type,
                    'batch' => 'batch-'.$batch_no,
                    'label' => $label,
                    'subunit_id' => $subunit,
                    'employee_id' => $receiver_id
                ];
            }
    
            $receivers = Receiver::insert($data_receiver);
            $forms = Form::insert($data);
            return response()->json($forms);
        }
    }

    public function destroy($batch)
    {
        $delete_receiver = Receiver::where('batch', $batch)->delete();
        $delete = Form::where('batch', $batch)->delete();
    }

    public function getApprovers(Request $request)
    {
        $batch = $request->input('batch');

        $approvers = DB::table('forms')
            ->leftJoin('employees', 'forms.employee_id', '=', 'employees.id')
            ->leftJoin('employee_positions', 'forms.employee_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->select([
                'forms.action',
                'forms.employee_id',
                'forms.level',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.image',
                'positions.position_name',
                'departments.department_name'
            ])
            ->where('forms.batch', '=', $batch)
            ->groupBy('forms.level')
            ->get();

        return ApproverResources::collection($approvers);
    }

    public function getSubunits(Request $request) 
    {
        $batch = $request->input('batch');

        $subunits = DB::table('forms')
            ->leftJoin('subunits', 'forms.subunit_id', '=', 'subunits.id')
            ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
            ->select([
                'subunits.code',
                'subunits.created_at',
                'subunits.department_id',
                'subunits.id',
                'subunits.subunit_name',
                'departments.department_name',
            ])
            ->where('forms.batch', '=', $batch)
            ->groupBy('forms.subunit_id')
            ->get()
            ->map(function ($subunits) {
                $subunits->subunit_name = $subunits->department_name.' - '.$subunits->subunit_name;
                return $subunits;
            });

        // dd($subunits);

        return response()->json($subunits);
    }

    public function getSubunitsReceiver(Request $request) 
    {
        $batch = $request->input('batch');

        $subunits = DB::table('receivers')
            ->leftJoin('subunits', 'receivers.subunit_id', '=', 'subunits.id')
            ->select([
                'subunits.code',
                'subunits.created_at',
                'subunits.department_id',
                'subunits.id',
                'subunits.subunit_name',
            ])
            ->where('receivers.batch', '=', $batch)
            ->groupBy('receivers.subunit_id')
            ->get();

        return response()->json($subunits);
    }

    public function getApproverAndReceiver(Request $request) 
    {
        $response = [
            'error' => false,
            'error_type' => '',
            'message' => ''
        ];
        
        $employee_id = Auth::user()->employee_id;
        $subunit_id = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employees.id',
                'positions.subunit_id',
            ])
            ->where('employee_positions.employee_id', '=', $employee_id)
            ->get()
            ->first();

        $subunit_id = ($subunit_id) ? $subunit_id->subunit_id : '';

        $form_type = $request->form_type;

        // dd($form_type);

        // check for approver 
        $approver = DB::table('forms')
            ->select([
                'id',
                'receiver_id'
            ])
            ->where('form_type', '=', $form_type)
            ->where('subunit_id', '=', $subunit_id)
            ->get()
            ->first();

        if ($approver) {
            if (!$approver->receiver_id) {
                $response['error'] = true;
                $response['error_type'] = 'receiver';
                $response['message'] = 'No Receiver has been found for this type of Form. Please inform the Administrator to solve this issue.';
            }
        } else {
            $response['error'] = true;
            $response['error_type'] = 'approver';
            $response['message'] = 'No Approver has been found for this type of Form. Please inform the Administrator to solve this issue.';
        }

        return response()->json($response);
    }

    public function importFormSetting(Request $request) {
        $file = $request->file('import_formsetting');

        if ($request->hasFile('import_formsetting')) {
            $import = new FormSettingImport();
            $import->import($file);
            $failures = $import->failures();
            $filename = 'formsetting-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    $failed_rows[] = [
                        $failed_row['form_type'],
                        $failed_row['label'],
                        $failed_row['batch'],
                        $failed_row['subunit'],
                        $failed_row['approver_id_prefix'],
                        $failed_row['approver_id_number'],
                        $failed_row['action'],
                        $failed_row['level'],
                        $failed_row['receiver_id_prefix'],
                        $failed_row['receiver_id_number'],
                        $failed_row['message']
                    ];
                }

                $export_error = new FormSettingExportError($failed_rows);
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
