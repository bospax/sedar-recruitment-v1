<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\Address as AddressResources;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index() {
        $addresses = DB::table('addresses')
            ->leftJoin('regions', 'addresses.region', '=', 'regions.reg_code')
            ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
            ->leftJoin('municipals', 'addresses.municipal', '=', 'municipals.citymun_code')
            ->leftJoin('barangays', 'addresses.barangay', '=', 'barangays.brgy_code')
            ->leftJoin('employees', 'addresses.employee_id', '=', 'employees.id')
            ->select([
                'addresses.id',
                'addresses.employee_id',
                'addresses.region',
                'addresses.province',
                'addresses.municipal',
                'addresses.barangay',
                'addresses.street',
                'addresses.zip_code',
                'addresses.detailed_address',
                'addresses.foreign_address',
                'addresses.address_remarks',
                'regions.reg_desc',
                'regions.reg_code',
                'provinces.prov_desc',
                'provinces.prov_code',
                'municipals.citymun_desc',
                'municipals.citymun_code',
                'barangays.brgy_desc',
                'barangays.brgy_code',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image'
            ])
            ->get();

        return AddressResources::collection($addresses);
        // return response()->json($addresses);
    }

    public function show($id) {

    }

    public function store(AddressRequest $request) {
        $address = new Address();

        $address->employee_id = $request->input('employee_id');
        $address->region = $request->input('region');
        $address->province = $request->input('province');
        $address->municipal = $request->input('municipal');
        $address->barangay = $request->input('barangay');
        $address->street = $request->input('street');
        $address->zip_code = $request->input('zip_code');
        $address->detailed_address = $request->input('detailed_address');
        $address->foreign_address = $request->input('foreign_address');
        $address->address_remarks = $request->input('address_remarks');

        if ($address->save()) {
            Helpers::LogActivity($address->id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - ADDRESS SECTION');
            return response()->json($address);
        }
    }

    public function update(AddressRequest $request, $id) {
        $address = Address::findOrFail($id);

        $address->employee_id = $request->input('employee_id');
        $address->region = $request->input('region');
        $address->province = $request->input('province');
        $address->municipal = $request->input('municipal');
        $address->barangay = $request->input('barangay');
        $address->street = $request->input('street');
        $address->zip_code = $request->input('zip_code');
        $address->detailed_address = $request->input('detailed_address');
        $address->foreign_address = $request->input('foreign_address');
        $address->address_remarks = $request->input('address_remarks');

        if ($address->save()) {
            Helpers::LogActivity($address->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - ADDRESS SECTION');

            $address = DB::table('addresses')
                ->leftJoin('regions', 'addresses.region', '=', 'regions.reg_code')
                ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
                ->leftJoin('municipals', 'addresses.municipal', '=', 'municipals.citymun_code')
                ->leftJoin('barangays', 'addresses.barangay', '=', 'barangays.brgy_code')
                ->leftJoin('employees', 'addresses.employee_id', '=', 'employees.id')
                ->select([
                    'addresses.id',
                    'addresses.employee_id',
                    'addresses.region',
                    'addresses.province',
                    'addresses.municipal',
                    'addresses.barangay',
                    'addresses.street',
                    'addresses.zip_code',
                    'addresses.detailed_address',
                    'addresses.foreign_address',
                    'addresses.address_remarks',
                    'regions.reg_desc',
                    'regions.reg_code',
                    'provinces.prov_desc',
                    'provinces.prov_code',
                    'municipals.citymun_desc',
                    'municipals.citymun_code',
                    'barangays.brgy_desc',
                    'barangays.brgy_code',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image'
                ])
                ->where('addresses.id', '=', $address->id)
                ->get();

            return AddressResources::collection($address);
        }
    }

    public function destroy($id) {
        $address = Address::findOrFail($id);

        if ($address->delete()) {
            Helpers::LogActivity($address->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - ADDRESS SECTION');
            return new AddressResources($address);
        }
    }

    public function fetchBarangays($citymun_code) {
        $barangays = DB::table('barangays')
            ->select([
                'id', 
                'brgy_code', 
                'brgy_desc', 
                'reg_code',
                'prov_code',
                'citymun_code'
            ])
            ->where('citymun_code', '=', $citymun_code)
            ->get();

        return response()->json($barangays);
    }

    public function fetchRegions() {
        $regions = DB::table('regions')
            ->select([
                'id', 
                'reg_desc',
                'reg_code'
            ])
            ->get();

        return response()->json($regions);
    }

    public function fetchProvinces($reg_code) {
        $provinces = DB::table('provinces')
            ->select([
                'id', 
                'prov_desc',
                'prov_code',
                'reg_code'
            ])
            ->where('reg_code', '=', $reg_code)
            ->get();

        return response()->json($provinces);
    }

    public function fetchMunicipals($prov_code) {
        $municipals = DB::table('municipals')
            ->select([
                'id', 
                'citymun_desc',
                'citymun_code',
                'reg_code',
                'prov_code'
            ])
            ->where('prov_code', '=', $prov_code)
            ->get();

        return response()->json($municipals);
    }

    public function getEmployeeAddress($id) {
        $addresses = DB::table('addresses')
            ->leftJoin('regions', 'addresses.region', '=', 'regions.reg_code')
            ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
            ->leftJoin('municipals', 'addresses.municipal', '=', 'municipals.citymun_code')
            ->leftJoin('barangays', 'addresses.barangay', '=', 'barangays.brgy_code')
            ->leftJoin('employees', 'addresses.employee_id', '=', 'employees.id')
            ->select([
                'addresses.id',
                'addresses.employee_id',
                'addresses.region',
                'addresses.province',
                'addresses.municipal',
                'addresses.barangay',
                'addresses.street',
                'addresses.zip_code',
                'addresses.detailed_address',
                'addresses.foreign_address',
                'addresses.address_remarks',
                'regions.reg_desc',
                'regions.reg_code',
                'provinces.prov_desc',
                'provinces.prov_code',
                'municipals.citymun_desc',
                'municipals.citymun_code',
                'barangays.brgy_desc',
                'barangays.brgy_code',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image'
            ])
            ->where('employees.id', '=', $id)
            ->get();

        return AddressResources::collection($addresses);
    }
}
