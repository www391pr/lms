<?php

namespace App\Http\Controllers;

use App\Models\CourseStudent;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\InstructorRating;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\FcmService;

class InstructorController extends Controller
{
    public function getInstructors(Request $request)
    {
        $instructorsQuery = Instructor::query()->with('categories');
        if ($categoryNames = $request->get('category')) {
            $instructorsQuery->whereHas('categories', function ($query) use ($categoryNames) {
                $query->whereIn('name', $categoryNames);
            });
        }
        switch ($request->get('sort_by')) {
            case 'views_asc':
                $instructorsQuery->orderBy('views', 'asc');
                break;
            case 'views_desc':
                $instructorsQuery->orderBy('views', 'desc');
                break;
            case 'rating':
                $instructorsQuery->orderBy('rating', 'desc');
                break;
        }
        $instructors = $instructorsQuery->paginate(10);
        $instructors->appends($request->query());
        return response()->json([
            'current_page' => $instructors->currentPage(),
            'data' => $instructors,
            'links' => [
                'previous' => $instructors->previousPageUrl(),
                'next' => $instructors->nextPageUrl(),
            ],
            'per_page' => $instructors->perPage(),
            'total' => $instructors->total(),
        ]);
    }
    public function show($id)
    {
        $instructor = Instructor::with(['categories','courses'])
            ->findOrFail($id);
        $enrolledCount = 0;
        $completedCount = 0;

        foreach ($instructor->courses as $course) {
            $enrolledCount  += $course->students()->where('status', 'enrolled')->count();
            $completedCount += $course->students()->where('status', 'completed')->count();
        }

        return response()->json([
            'data' => $instructor,
            'students' => [
                'enrolled'  => $enrolledCount,
                'completed' => $completedCount,
            ],
        ], 200);
    }

    public function rate(Request $request, $id)
    {
        Instructor::findOrFail($id);
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);
        // dd(auth()->user()->student->id, $id,$request->rating);

        InstructorRating::updateOrCreate(
            [
                'instructor_id' => $id,
                'student_id' =>auth()->user()->student->id,
            ],
            ['rating' => $request->rating]
        );

        $averageRating = InstructorRating::where('instructor_id', $id)->average('rating');
        Instructor::where('id', $id)->update(['rating' => $averageRating]);

        return response()->json([
            'message' => 'Rating submitted successfully!',
            'rating' => $averageRating,
        ]);
    }

    public function addView($id)
    {
        $instructor = Instructor::findOrFail($id);
        $instructor->increment('views');

        return response()->json([
            'message'           => 'View recorded successfully',
            'instructor_views'  => $instructor->views,
        ], 200);
    }


    public function uploadCv(Request $request)
    {
        $request->validate([
            'cv' => 'required|mimes:pdf,doc,docx|max:5120', // allow pdf/doc/docx
        ]);

        $instructor = auth()->user()->instructor;

        if ($instructor->verified) {
            return response()->json([
                'message' => 'Your account is already verified. You cannot upload a new CV.'
            ], 403);
        }

        if ($instructor->cv_path && Storage::disk('public')->exists($instructor->cv_path)) {
            return response()->json([
                'message' => 'Your account is pending for admin review please be patient the process could take up to 48 hours.'
            ], 400);
        }

        // store new CV
        $path = $request->file('cv')->store('cvs', 'public');

        $instructor->update([
            'cv_path' => $path,
        ]);

        return response()->json([
            'message'    => 'CV uploaded successfully, pending verification.',
            'instructor' => $instructor,
        ]);
    }


    public function enable(Instructor $instructor)
    {
        $instructor->update(['enabled' => true]);

        return response()->json([
            'message' => 'Instructor enabled successfully.',
            'instructor' => $instructor
        ]);
    }

    public function disable(Instructor $instructor)
    {
        $instructor->update(['enabled' => false]);
        if ($instructor->user) {
            $instructor->user->tokens()->delete();
        }
        return response()->json([
            'message' => 'Instructor disabled successfully.',
            'instructor' => $instructor
        ]);
    }


    public function acceptCv(Instructor $instructor , FcmService $fcm)
    {
        if (!$instructor->cv_path) {
            return response()->json([
                'message' => 'No CV found to accept.'
            ], 400);
        }

        $instructor->update([
            'verified' => true,
        ]);

        $fcm->sendToUser(
            user: $instructor->user,
            title: 'CV Verified 🎉',
            body:  'Your instructor account has been verified.',
            data:  ['type' => 'instructor_verification', 'instructor_id' => (string)$instructor->id]
        );

        return response()->json([
            'message'    => 'Instructor CV has been accepted and account verified.',
            'instructor' => $instructor,
        ]);
    }

    public function rejectCv(Instructor $instructor)
    {
        if ($instructor->cv_path && Storage::disk('public')->exists($instructor->cv_path)) {
            Storage::disk('public')->delete($instructor->cv_path);
        }

        $instructor->update([
            'cv_path'  => null,
            'verified' => false,
        ]);

        return response()->json([
            'message'    => 'Instructor CV has been rejected and deleted.',
            'instructor' => $instructor,
        ]);
    }



    public function instructorDashboard()
    {
        $instructor = auth()->user()->instructor;

        // Revenue (only this instructor's courses)
        $totalRevenue = Payment::whereHas('course', function ($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id);
        })->sum('amount');

        // Students enrolled in this instructor’s courses
        $totalStudents = CourseStudent::whereHas('course', function ($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id);
        })->distinct('student_id')->count('student_id');

        // Total courses
        $totalCourses = $instructor->courses()->count();

        // Instructor rating
        $yourRating = $instructor->rating; // from instructors table

        // Courses rating (average rating of all course_student ratings for this instructor)
        $coursesRating = CourseStudent::whereHas('course', function ($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id);
        })->avg('rating');

        $topCourses = Payment::select(
            'course_id',
            DB::raw('SUM(amount) as revenue')
        )
            ->whereHas('course', function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })
            ->groupBy('course_id')
            ->orderByDesc('revenue')
            ->take(5)
            ->with('course:id,title')
            ->get()
            ->map(function ($p) {
                $avgRating = CourseStudent::where('course_id', $p->course_id)->avg('rating');
                return [
                    'name' => $p->course->title,
                    'revenue' => $p->revenue,
                    'avg_rating' => round($avgRating, 2),
                ];
            });

        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd   = now()->subMonths($i)->endOfMonth();

            $monthlySales = Payment::whereHas('course', function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $data[] = $monthlySales;
        }
        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalStudents' => $totalStudents,
            'totalCourses' => $totalCourses,
            'yourRating' => $yourRating,
            'coursesRating' => round($coursesRating, 2),
            'topCourses' => $topCourses,
            'data' => $data,
        ]);
    }
}
