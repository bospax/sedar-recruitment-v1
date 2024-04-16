<?php

namespace App\Http\Controllers;

use App\Exports\CoursesExport;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\Course as CourseResources;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index()
    {
        $courses = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'course_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $courses = DB::table('courses')->select(['id', 'course_name', 'created_at'])
                    ->where('course_name', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $courses = DB::table('courses')->select(['id', 'course_name', 'created_at'])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return CourseResources::collection($courses);
    }

    public function show($id)
    {
        $course = Course::findOrFail($id);
        return new CourseResources($course);
    }

    public function store(CourseRequest $request)
    {
        $course = new Course();
        $course->course_name = $request->input('course_name');

        if ($course->save()) {
            return new CourseResources($course);
        }
    }

    public function update(CourseRequest $request, $id)
    {
        $course = Course::findOrFail($id);
        $course->course_name = $request->input('course_name');

        // return the updated or newly added article
        if ($course->save()) {
            return new CourseResources($course);
        }
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        if ($course->delete()) {
            return new CourseResources($course);
        }
    }

    public function export() 
    {
        $query = DB::table('courses')->select(['id', 'course_name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'courses-exportall.xlsx';
        $course_export = new CoursesExport($query);
        $course_export->store('public/files/'.$filename);
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

            $query = DB::table('courses')->select(['id', 'course_name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'courses-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $course_export = new CoursesExport($query);
                $course_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'course_name' : 'created_at';            

            $courses = DB::table('courses')->select(['id', 'course_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return CourseResources::collection($courses);
        }
    }
}
