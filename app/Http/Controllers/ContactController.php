<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Models\Contact;
use App\Models\EmployeeContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\EmployeeContact as ResourcesEmployeeContacts;
use App\Http\Resources\EmployeeFile;

class ContactController extends Controller
{
    public function index()
    {
        
    }

    public function show($id)
    {
        
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => 'required'
        ]);

        if (empty($request->input('contacts'))) {
            throw ValidationException::withMessages([
                'contact_type' => ['Contacts are required.']
            ]);
        }

        $employee_id = $request->input('employee_id');
        $data = [];

        foreach ($request->input('contacts') as $key => $contact) {
            $contact = json_decode($contact);
            
            $data[] = [
                'employee_id' => $employee_id,
                'contact_type' => $contact->contact_type,
                'contact_details' => $contact->contact_details,
                'description' => $contact->description,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
        }

        Helpers::LogActivity($employee_id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - CONTACT SECTION');
        EmployeeContact::insert($data);
    }

    public function update(Request $request, $id)
    {
        // dd($request);

        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => 'required'
        ]);

        if (empty($request->input('contacts'))) {
            throw ValidationException::withMessages([
                'contact_type' => ['Contacts are required.']
            ]);
        }

        $employee_id = $request->input('employee_id');
        $data = [];

        foreach ($request->input('contacts') as $key => $file) {
            $file = json_decode($file);
            
            $data = [
                'employee_id' => $employee_id,
                'contact_type' => $file->contact_type,
                'contact_details' => $file->contact_details,
                'description' => $file->description,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
        }

        $update = DB::table('employee_contacts')
            ->where('id', $id)
            ->update($data);

        $updated = DB::table('employee_contacts')
            ->select([
                'id',
                'employee_id',
                'contact_type',
                'contact_details',
                'description',
            ])
            ->where('id', $id)
            ->get();

        Helpers::LogActivity($employee_id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - CONTACT SECTION');
        return ResourcesEmployeeContacts::collection($updated);
    }

    public function destroy($id) {
        $contact = EmployeeContact::findOrFail($id);

        if ($contact->delete()) {
            Helpers::LogActivity($contact->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - CONTACT SECTION');
            return new ResourcesEmployeeContacts($contact);
        }
    }

    public function getContactsTable($employee_id) {
        $contacts = DB::table('employee_contacts')
            ->select([
                'employee_contacts.id',
                'employee_contacts.employee_id',
                'employee_contacts.contact_type',
                'employee_contacts.contact_details',
                'employee_contacts.description',
            ])
            ->where('employee_id', '=', $employee_id)
            ->get();

        return ResourcesEmployeeContacts::collection($contacts);
    }
}
