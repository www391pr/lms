<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Category;
use App\Models\Instructor;
use App\Traits\FilterCourses;
use App\Traits\SortCourses;

class SearchController extends Controller
{
    use filterCourses, sortCourses;
    public function search(Request $request)
    {
        if (!$request->has('key') || !$request->get('key')) {
            return response()->json([
                'message' => 'Please enter a search key.'
            ], 400);
        }
        $key = $request->get('key');
        $coursesQuery = Course::with(['instructor', 'categories:id,name', 'students:id,status']);
        $coursesQuery = $this->filterCourses($request, $coursesQuery);
        $sortBy = $request->get('sort_by');
        if ($sortBy) {
            $this->sortCourses($sortBy, $coursesQuery);
        }

        $categoriesQuery = Category::query();
        $categoriesQuery->where('name', 'like', '%'.$key.'%');

        $instructorsQuery = Instructor::query();
        $instructorsQuery->where('full_name', 'like', '%'.$key.'%');

        switch ($request->get('sort_by')) {
            case 'views_asc':
                $instructorsQuery->orderBy('views', 'asc');
                break;
            case 'views_desc':
                $instructorsQuery->orderBy('views', 'desc');
                break;
        }

        $type = $request->get('type');
        if ($type === 'courses') {
            if(auth()->user()->isInstructor()) {
                $coursesQuery->where('instructor_id', auth()->user()->instructor->id);
                $courses = $coursesQuery->get();
            }else if (auth()->user()->isStudent()){
            $courses = $coursesQuery->get()->map(function ($course) {
                $course->status = $course->students()
                    ->where('student_id', auth()->user()->student->id)->pluck('status')->first();
                unset($course->students);
                return $course;
            });
            }
            else{
                $courses = $coursesQuery->get();
            }
            return response()->json(['courses' => $courses], 200);
        }
        if ($type === 'categories') {
            $categories = $categoriesQuery->get();
            return response()->json(['categories' => $categories], 200);
        }
        if ($type === 'instructors') {
            $instructors = $instructorsQuery->get();
            return response()->json(['instructors' => $instructors], 200);
        }

        $courses = $coursesQuery->get()->map(function ($course) {
            $course->status = $course->students()
                ->where('student_id', auth()->user()->student->id)->pluck('status')->first();
            unset($course->students);
            return $course;
        });
        $categories = $categoriesQuery->get();
        $instructors = $instructorsQuery->get();

        if ($courses->isEmpty() && $categories->isEmpty() && $instructors->isEmpty()) {
            return response()->json(['message' => 'No courses, categories, or instructors found.'], 404);
        }

        return response()->json([
            'courses' => $courses,
            'categories' => $categories,
            'instructors' => $instructors,
        ], 200);
    }

    public function autoComplete(Request $request)
    {
        $key = $request->get('key', '');
        if (!empty($key)) {
            $courses = Course::where('title', 'LIKE', $key . '%')
                ->limit(5)
                ->pluck('title');
            $instructors = Instructor::where('full_name', 'LIKE', $key . '%')
                ->limit(5)
                ->pluck('full_name');
            $categories = Category::where('name', 'LIKE', $key . '%')
                ->limit(5)
                ->pluck('name');

            return response()->json([
                'courses' => $courses,
                'instructors' => $instructors,
                'categories' => $categories,
            ], 200);
        }

        return response()->json([
            'message' => 'key query parameter is required',
        ], 400);
    }
}
