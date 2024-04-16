<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormSettingRequest;
use App\Http\Resources\FormSetting as FormSettingResources;
use App\Imports\FormSettingImport;
use App\Models\FormSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormSettingController extends Controller
{
    public function index() {
        $form_settings = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'form_settings.label' : 'form_settings.created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $form_settings = DB::table('form_settings')
                    ->leftJoin('subunits', 'form_settings.subunit_id', '=', 'subunits.id')
                    ->select([
                        'form_settings.id',
                        'form_settings.form_type',
                        'form_settings.subunit_id',
                        'form_settings.number_of_levels', 
                        'form_settings.label', 
                        'form_settings.created_at',
                        'subunits.subunit_name'
                    ])
                    ->where('form_settings.label', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(4);
            } else {
                $form_settings = DB::table('form_settings')
                    ->leftJoin('subunits', 'form_settings.subunit_id', '=', 'subunits.id')
                    ->select([
                        'form_settings.id',
                        'form_settings.form_type',
                        'form_settings.subunit_id',
                        'form_settings.number_of_levels', 
                        'form_settings.label', 
                        'form_settings.created_at',
                        'subunits.subunit_name'
                    ])
                    ->orderBy($field, $sort)
                    ->paginate(4);
            }
        }

        return FormSettingResources::collection($form_settings);
    }

    public function show($id) {

    }

    public function store(FormSettingRequest $request) {
        $form_setting = new FormSetting();
        $form_setting->form_type = $request->input('form_type');
        $form_setting->subunit_id = $request->input('subunit_id');
        $form_setting->number_of_levels = $request->input('number_of_levels');
        $form_setting->label = $request->input('label');

        if ($form_setting->save()) {
            return new FormSettingResources($form_setting);
        }
    }

    public function update(FormSettingRequest $request, $id) {
        $form_setting = FormSetting::findOrFail($id);
        $form_setting->form_type = $request->input('form_type');
        $form_setting->subunit_id = $request->input('subunit_id');
        $form_setting->number_of_levels = $request->input('number_of_levels');
        $form_setting->label = $request->input('label');

        if ($form_setting->save()) {
            $form_setting = DB::table('form_settings')
                ->leftJoin('subunits', 'form_settings.subunit_id', '=', 'subunits.id')
                ->select([
                    'form_settings.id',
                    'form_settings.form_type',
                    'form_settings.subunit_id',
                    'form_settings.number_of_levels', 
                    'form_settings.label', 
                    'form_settings.created_at',
                    'subunits.subunit_name'
                ])
                ->where('form_settings.id', '=', $form_setting->id)
                ->get();
            
            return FormSettingResources::collection($form_setting);
        }
    }

    public function destroy($id) {
        $form_setting = FormSetting::findOrFail($id);

        if ($form_setting->delete()) {
            return new FormSettingResources($form_setting);
        }
    }
}
