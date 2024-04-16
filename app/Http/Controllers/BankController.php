<?php

namespace App\Http\Controllers;

use App\Exports\BanksExport;
use App\Http\Requests\BankRequest;
use App\Http\Resources\Bank as BankResources;
use App\Models\Bank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    public function index()
    {
        $banks = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'bank_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $banks = DB::table('banks')->select(['id', 'bank_name', 'created_at'])
                    ->where('bank_name', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $banks = DB::table('banks')->select(['id', 'bank_name', 'created_at'])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return BankResources::collection($banks);
    }

    public function show($id)
    {
        $bank = Bank::findOrFail($id);
        return new BankResources($bank);
    }

    public function store(BankRequest $request)
    {
        $bank = new Bank();
        $bank->bank_name = $request->input('bank_name');

        if ($bank->save()) {
            return new BankResources($bank);
        }
    }

    public function update(BankRequest $request, $id)
    {
        $bank = Bank::findOrFail($id);
        $bank->bank_name = $request->input('bank_name');

        // return the updated or newly added article
        if ($bank->save()) {
            return new BankResources($bank);
        }
    }

    public function destroy($id)
    {
        $bank = Bank::findOrFail($id);

        if ($bank->delete()) {
            return new BankResources($bank);
        }
    }

    public function export() 
    {
        $query = DB::table('banks')->select(['id', 'bank_name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'banks-exportall.xlsx';
        $bank_export = new BanksExport($query);
        $bank_export->store('public/files/'.$filename);
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

            $query = DB::table('banks')->select(['id', 'bank_name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'banks-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $bank_export = new BanksExport($query);
                $bank_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'bank_name' : 'created_at';            

            $banks = DB::table('banks')->select(['id', 'bank_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return BankResources::collection($banks);
        }
    }
}
