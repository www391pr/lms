<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function attachMainCategories(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $user = auth()->user();

        if (!$user->student) {
            return response()->json([
                'error' => 'User does not have an associated student record.'
            ], 400);
        }

        $student = $user->student;

        $student->categories()->syncWithoutDetaching($request->category_ids);

        $subCategories = Category::whereIn('parent_id', $request->category_ids)->get();

        return response()->json([
            'message' => 'Main categories attached successfully',
            'sub_categories' => $subCategories
        ], 200);
    }

    public function attachSubCategories(Request $request)
    {
        $request->validate([
            'sub_category_ids' => 'required|array',
            'sub_category_ids.*' => 'exists:categories,id',
        ]);

        $user = auth()->user();

        if (!$user->student) {
            return response()->json([
                'error' => 'User does not have an associated student record.'
            ], 400);
        }

        $student = $user->student;

        $student->categories()->syncWithoutDetaching($request->sub_category_ids);

        return response()->json([
            'message' => 'Sub-categories attached successfully',
        ]);
    }



//recive requests like this
//GET /student/courses?status=wishlist
//GET /student/courses?status=enrolled
//GET /student/courses?status=completed
    public function getStudentCourses(Request $request)
    {
        $student = auth()->user()->student;

        // validate the incoming status
        $status = $request->input('status'); // expected: enrolled | completed | wishlist
        if (!in_array($status, ['enrolled', 'completed', 'wishlist'])) {
            return response()->json([
                'message' => 'Invalid status'
            ], 400);
        }

        // fetch courses with the given status from pivot
        $courses = Course::whereHas('students', function ($q) use ($student, $status) {
            $q->where('student_id', $student->id)
                ->where('status', $status);
        })->get();

        // calculate progress for each course
        $courses->transform(function ($course) use ($student) {
            $totalDuration = $course->total_duration; // from courses table

            // get section IDs only (without loading the full relation)
            $sectionIds = DB::table('sections')
                ->where('course_id', $course->id)
                ->pluck('id');

            $completedDuration = DB::table('lesson_student')
                ->join('lessons', 'lesson_student.lesson_id', '=', 'lessons.id')
                ->where('lesson_student.student_id', $student->id)
                ->whereIn('lessons.section_id', $sectionIds)
                ->sum('lessons.duration');

            $progress = $totalDuration > 0
                ? round(($completedDuration / $totalDuration) * 100, 2)
                : 0;

            $course->progress = $progress;

            // remove sections if accidentally loaded
            unset($course->sections);

            return $course;
        });

        return response()->json([
            'status' => $status,
            'courses' => $courses
        ]);
    }

    public function getStudentCategories(){
        $student = auth()->user()->student;
        $categories = $student->categories;
        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function addToWishlist(Request $request, Course $course)
    {
        $student = auth()->user()->student;

        if (!$student) {
            return response()->json([
                'message' => 'Only students can add courses to wishlist.'
            ], 403);
        }
        $student->courses()->syncWithoutDetaching([
            $course->id => ['status' => 'wishlist']
        ]);

        return response()->json([
            'message' => 'Course added to wishlist successfully.',
            'course'  => $course,
        ]);
    }

}
