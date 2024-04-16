<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Requests\EmployeeAccountRequest;
use App\Http\Resources\EmployeeAccount AS EmployeeAccountResources;
use App\Models\EmployeeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeAccountController extends Controller
{
    public function index() {
        $employee_accounts = DB::table('employee_accounts')
            ->leftJoin('employees', 'employee_accounts.employee_id', '=', 'employees.id')
            ->select([
                'employee_accounts.id',
                'employee_accounts.employee_id',
                'employee_accounts.sss_no',
                'employee_accounts.pagibig_no',
                'employee_accounts.philhealth_no',
                'employee_accounts.tin_no',
                'employee_accounts.bank_name',
                'employee_accounts.bank_account_no',
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

        return EmployeeAccountResources::collection($employee_accounts);
        // return response()->json($employee_accounts);
    }

    public function show($id) {

    }

    public function store(EmployeeAccountRequest $request) {
        $employee_account = new EmployeeAccount();

        $employee_account->employee_id = $request->input('employee_id');
        $employee_account->sss_no = $request->input('sss_no');
        $employee_account->pagibig_no = $request->input('pagibig_no');
        $employee_account->philhealth_no = $request->input('philhealth_no');
        $employee_account->tin_no = $request->input('tin_no');
        $employee_account->bank_name = $request->input('bank_name');
        $employee_account->bank_account_no = $request->input('bank_account_no');

        if ($employee_account->save()) {
            Helpers::LogActivity($employee_account->id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - ACCOUNT SECTION');
            return response()->json($employee_account);
        }
    }

    public function update(EmployeeAccountRequest $request, $id) {
        $employee_account = EmployeeAccount::findOrFail($id);

        $employee_account->employee_id = $request->input('employee_id');
        $employee_account->sss_no = $request->input('sss_no');
        $employee_account->pagibig_no = $request->input('pagibig_no');
        $employee_account->philhealth_no = $request->input('philhealth_no');
        $employee_account->tin_no = $request->input('tin_no');
        $employee_account->bank_name = $request->input('bank_name');
        $employee_account->bank_account_no = $request->input('bank_account_no');

        if ($employee_account->save()) {
            Helpers::LogActivity($employee_account->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - ACCOUNT SECTION');
            $employee_account = DB::table('employee_accounts')
                ->leftJoin('employees', 'employee_accounts.employee_id', '=', 'employees.id')
                ->select([
                    'employee_accounts.id',
                    'employee_accounts.employee_id',
                    'employee_accounts.sss_no',
                    'employee_accounts.pagibig_no',
                    'employee_accounts.philhealth_no',
                    'employee_accounts.tin_no',
                    'employee_accounts.bank_name',
                    'employee_accounts.bank_account_no',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                ])
                ->where('employee_accounts.id', '=', $employee_account->id)
                ->get();

            return EmployeeAccountResources::collection($employee_account);
        }
    }

    public function destroy($id) {
        $employee_account = EmployeeAccount::findOrFail($id);

        if ($employee_account->delete()) {
            Helpers::LogActivity($employee_account->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - ACCOUNT SECTION');
            return new EmployeeAccountResources($employee_account);
        }
    }

    public function getEmployeeAccounts($id) {
        $employee_accounts = DB::table('employee_accounts')
            ->leftJoin('employees', 'employee_accounts.employee_id', '=', 'employees.id')
            ->select([
                'employee_accounts.id',
                'employee_accounts.employee_id',
                'employee_accounts.sss_no',
                'employee_accounts.pagibig_no',
                'employee_accounts.philhealth_no',
                'employee_accounts.tin_no',
                'employee_accounts.bank_name',
                'employee_accounts.bank_account_no',
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

        return EmployeeAccountResources::collection($employee_accounts);
    }
}
