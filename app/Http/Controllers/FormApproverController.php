<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormApproverRequest;
use App\Http\Resources\FormApprover as FormApproverResources;
use App\Models\FormApprover;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormApproverController extends Controller
{
    public function index() {
        
    }

    public function show($id) {

    }

    public function store(FormApproverRequest $request) {
        $form_approver = new FormApprover();
        $form_approver->employee_id = $request->input('employee_id');
        $form_approver->form_setting_id = $request->input('form_setting_id');
        $form_approver->action = $request->input('action');
        $form_approver->approved_mark = $request->input('approved_mark');
        $form_approver->rejected_mark = $request->input('rejected_mark');

        $last_row = DB::table('form_approvers')->latest('level')->where('form_setting_id', '=', $request->input('form_setting_id'))->first();
        $form_approver->level = (!empty($last_row)) ? $last_row->level + 1 : 1;

        if ($form_approver->save()) {
            return new FormApproverResources($form_approver);
        }
    }

    public function update(FormApproverRequest $request, $id) {
        $form_approver = FormApprover::findOrFail($id);
        $form_approver->employee_id = $request->input('employee_id');
        $form_approver->form_setting_id = $request->input('form_setting_id');
        $form_approver->action = $request->input('action');
        $form_approver->approved_mark = $request->input('approved_mark');
        $form_approver->rejected_mark = $request->input('rejected_mark');

        if ($form_approver->save()) {
            $form_approver = DB::table('form_approvers')
                ->leftJoin('employees', 'form_approvers.employee_id', '=', 'employees.id')
                ->leftJoin('employee_positions AS epos', 'form_approvers.employee_id', '=', 'epos.employee_id')
                ->leftJoin('positions', 'epos.position_id', '=', 'positions.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->select([
                    'form_approvers.id',
                    'form_approvers.employee_id',
                    'form_approvers.form_setting_id',
                    'form_approvers.level', 
                    'form_approvers.action', 
                    'form_approvers.approved_mark',
                    'form_approvers.rejected_mark',
                    'form_approvers.created_at',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                    'positions.position_name',
                    'subunits.subunit_name'
                ])
                ->where('form_approvers.id', '=', $form_approver->id)
                ->get();

            return FormApproverResources::collection($form_approver);
        }
    }

    public function destroy($id) {
        $form_approver = FormApprover::findOrFail($id);

        if ($form_approver->delete()) {
            return new FormApproverResources($form_approver);
        }
    }

    public function fetchFormApprovers($form_setting_id) {
        $form_approvers = DB::table('form_approvers')
            ->leftJoin('employees', 'form_approvers.employee_id', '=', 'employees.id')
            ->leftJoin('employee_positions AS epos', 'form_approvers.employee_id', '=', 'epos.employee_id')
            ->leftJoin('positions', 'epos.position_id', '=', 'positions.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->select([
                'form_approvers.id',
                'form_approvers.employee_id',
                'form_approvers.form_setting_id',
                'form_approvers.level', 
                'form_approvers.action', 
                'form_approvers.approved_mark',
                'form_approvers.rejected_mark',
                'form_approvers.created_at',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
                'positions.position_name',
                'subunits.subunit_name'
            ])
            ->where('form_approvers.form_setting_id', '=', $form_setting_id)
            ->orderBy('level', 'asc')
            ->get();

        return FormApproverResources::collection($form_approvers);
    }

    public function changeOrder() {
        $form_approvers = request('form_approvers');

        foreach ($form_approvers as $form_approver) {
            FormApprover::find($form_approver['id'])->update(['level' => $form_approver['level']]);
        }

        return response()->json($form_approvers);
    }
}
