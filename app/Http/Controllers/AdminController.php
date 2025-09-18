<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function pendingCvs()
    {
        $instructors = Instructor::whereNotNull('cv_path')
            ->where('verified', false)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message'     => 'Pending CVs retrieved successfully.',
            'instructors' => $instructors,
        ]);
    }

    public function adminDashboard()
    {
        $totalRevenue = Payment::sum('amount'); // total payments
        $totalStudents = Student::count();
        $totalCourses = Course::count();
        $totalInstructors = Instructor::count();

        // Top performing courses by revenue
        $topCourses = Payment::select('course_id', DB::raw('SUM(amount) as revenue'))
            ->groupBy('course_id')
            ->orderByDesc('revenue')
            ->take(5)
            ->with(['course:id,title,instructor_id', 'course.instructor:id,full_name'])
            ->get()
            ->map(function ($p) {
                return [
                    'name' => $p->course->title,
                    'revenue' => $p->revenue,
                    'instructor' => $p->course->instructor->full_name ?? null,
                ];
            });

        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd   = now()->subMonths($i)->endOfMonth();

            $monthlySales = Payment::whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $data[] = $monthlySales;
        }

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalStudents' => $totalStudents,
            'totalCourses' => $totalCourses,
            'totalInstructors' => $totalInstructors,
            'topCourses' => $topCourses,
            'data' => $data,
        ]);
    }
}
