<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\DivisionCategory;
use App\Models\JobBand;
use App\Models\JobRate;
use App\Models\Location;
use App\Models\Position;
use App\Models\Subunit;
use App\Models\Title;
use Illuminate\Http\Request;

class MasterlistController extends Controller
{
    public function changeStatus(Request $request) {
        $id = $request->input('id');
        $current_status = $request->input('current_status');
        $module = $request->input('module');
        $data = '';

        switch ($module) {
            case 'location':
                $data = Location::findOrFail($id);
                break;

            case 'division':
                $data = Division::findOrFail($id);
                break;

            case 'category':
                $data = DivisionCategory::findOrFail($id);
                break;

            case 'company':
                $data = Company::findOrFail($id);
                break;

            case 'department':
                $data = Department::findOrFail($id);
                break;

            case 'subunit':
                $data = Subunit::findOrFail($id);
                break;

            case 'position':
                $data = Position::findOrFail($id);
                break;

            case 'jobband':
                $data = JobBand::findOrFail($id);
                break;

            case 'jobrate':
                $data = JobRate::findOrFail($id);
                break;

            case 'title':
                $data = Title::findOrFail($id);
                break;

            default:
                # code...
                break;
        }

        $data->status = ($current_status == 'active') ? 'inactive' : 'active';
        $data->status_description = ($current_status == 'active') ? 'DEACTIVATED' : 'ACTIVE';

        if ($data->update()) {}
    }
}


