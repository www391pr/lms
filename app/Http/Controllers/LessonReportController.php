<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonReport;
use Illuminate\Http\Request;

class LessonReportController extends Controller
{
    public function store(Request $request, Lesson $lesson)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'message'    => 'required|string|max:1000',
        ]);

        $student = auth()->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Only students can report lessons'], 403);
        }
        $lesson = Lesson::find($request->lesson_id);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson not found.'], 404);
        }

        $report = LessonReport::create([
            'student_id' => $student->id,
            'lesson_id'  => $request->lesson_id,
            'message'     => $request->message,
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'report'  => $report,
        ]);
    }

    // admin fetches all reports
    public function getAllReports()
    {
        $reports = LessonReport::with(['student.user', 'lesson.section.course.instructor'])
            ->orderBy('created_at', 'desc')->get();

        $reports->each(function ($report) {
            $report->course = $report->lesson->section->course;
            });
        $reports->each(function ($report) {
            unset($report->lesson->section);
        });
        return response()->json(['reports'=>$reports]);
    }
    public function markAsReviewed(LessonReport $report)
    {
        if ($report->status === 'reviewed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Report already reviewed.'
            ], 400);
        }
        $report->status = 'reviewed';
        $report->save();
        return response()->json([
            'message' => 'Report marked as reviewed successfully.',
            'data' => $report
        ]);
    }
}
