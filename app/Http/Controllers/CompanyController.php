<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\CompaniesExport;
use App\Http\Requests\CompanyRequest;
use App\Http\Resources\Company as CompanyResources;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'company_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $companies = DB::table('companies')
                    ->select([
                        'id', 
                        'code', 
                        'company_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    ->where('company_name', 'LIKE', $value)
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $companies = DB::table('companies')
                    ->select([
                        'id', 
                        'code', 
                        'company_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return CompanyResources::collection($companies);
    }

    public function show($id)
    {
        $company = Company::findOrFail($id);
        return new CompanyResources($company);
    }

    public function store(CompanyRequest $request)
    {
        Helpers::checkDuplicate('companies', 'company_name', $request->input('company_name'));

        $company = new Company();
        $company->code = Helpers::generateCodeNewVersion('companies', 'CO');
        $company->company_name = $request->input('company_name');
        $company->status = 'active';
        $company->status_description = 'ACTIVE';

        if ($company->save()) {
            Helpers::LogActivity($company->id, 'MASTERLIST - COMPANY', 'ADDED NEW COMPANY DATA');
            return new CompanyResources($company);
        }
    }

    public function update(CompanyRequest $request, $id)
    {
        Helpers::checkDuplicate('companies', 'company_name', $request->input('company_name'), $id);

        $company = Company::findOrFail($id);
        $company->company_name = $request->input('company_name');

        // return the updated or newly added article
        if ($company->save()) {
            Helpers::LogActivity($company->id, 'MASTERLIST - COMPANY', 'UPDATED COMPANY DATA');
            return new CompanyResources($company);
        }
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);

        if ($company->delete()) {
            Helpers::LogActivity($company->id, 'MASTERLIST - COMPANY', 'DELETED COMPANY DATA');
            return new CompanyResources($company);
        }
    }

    public function export() 
    {
        $query = DB::table('companies')
            ->select([
                'id', 
                'code', 
                'company_name', 
                'status',
                'status_description',
                'created_at'
            ])
            ->orderBy('id', 'desc');
        $filename = 'companies-exportall.xlsx';
        $company_export = new CompaniesExport($query);
        $company_export->store('public/files/'.$filename);
        $link = '/storage/files/'.$filename;
        
        return response()->json([
            'link' => $link
        ]);
    }

    public function exportByDate($daterange)
    {
        if (!empty($daterange)) {
            $daterange = explode('-', $daterange);
            $from = $daterange[0];
            $to = $daterange[1];
            $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
            $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

            $query = DB::table('companies')
                ->select([
                    'id', 
                    'code', 
                    'company_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'companies-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $company_export = new CompaniesExport($query);
                $company_export->store('public/files/'.$filename);
            }

            return response()->json([
                'link' => $link,
                'count' => $count
            ]);
        }
    }

    public function sortData() 
    {
        $field = request('field');
        $sort = request('sort');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'company_name' : 'created_at';            

            $companies = DB::table('companies')->select(['id', 'code', 'company_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return CompanyResources::collection($companies);
        }
    }

    public function getCompanies() 
    {
        $companies = DB::table('companies')
            ->select([
                'id', 
                'code', 
                'company_name', 
                'status',
                'status_description',
                'created_at'
            ])
            // ->whereNull('deleted_at')
            ->where('status', '!=', 'inactive')
            ->orderBy('company_name', 'desc')
            ->get();

        return CompanyResources::collection($companies);
    }
}
