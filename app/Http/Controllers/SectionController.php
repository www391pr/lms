<?php

namespace App\Http\Controllers;

use App\Events\SectionDurationUpdated;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SectionController extends Controller
{

    public function getAllSections($courseId)
    {
        $student = auth()->user()->student;

        $sections = Section::where('course_id', $courseId)
            ->orderBy('order')
            ->with(['lessons' => function ($query) use ($student) {
                $query->orderBy('order')
                    ->with(['students' => function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    }]);
            }])
            ->get();
        if ($sections->isEmpty()) {
            return response()->json(['Message'=> 'No Sections Found'], 404);
        }
        $sections->each(function ($section) use ($student) {
            $section->lessons->each(function ($lesson) use ($student) {
                $lesson->completed = $lesson->students->isNotEmpty();
                unset($lesson->students);
            });
        });

        return response()->json(['sections' => $sections]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
        ]);
        if(Course::find($request->course_id)->instructor_id!=auth()->user()->instructor->id)
            return response()->json(['message' => 'Unauthorized'], 401);
        $order = Section::where('course_id', $request->course_id)->max('order') + 1;
        $section = Section::create([
            'title' => $request->title,
            'course_id' => $request->course_id,
            'order' => $order,
        ]);

        return response()->json(['message' => 'Section created successfully', 'section' => $section], 201);
    }

    public function update(Request $request, $id)
    {
        $section = Section::with('course')->findOrFail($id);
        if (auth()->user()->id !== $section->course->instructor->user_id) {
            return response()->json('Unauthorized',403);
        }
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $section->update([
            'title' => $request->title,
        ]);

        return response()->json(['message' => 'Section updated successfully', 'section' => $section]);
    }

    public function destroy($id)
    {
        $section = Section::with('course')->findOrFail($id);
        if (auth()->user()->id !== $section->course->instructor->user_id) {
            return response()->json('Unauthorized', 403);
        }
        foreach ($section->lessons as $lesson) {
            $path = 'videos/' .$lesson->file_name;
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
                $prefix = pathinfo($lesson->file_name, PATHINFO_FILENAME);
                $subtitleFiles = Storage::disk('local')->files('subtitles');
                foreach ($subtitleFiles as $file) {
                    $filename = basename($file);
                    if (strpos($filename, $prefix . '-') === 0 && substr($filename, -4) === '.vtt') {
                        Storage::disk('local')->delete($file);
                    }
                }
            }
        }

        $section->delete();

        event(new SectionDurationUpdated($section));

        return response()->json(['message' => 'Section deleted successfully']);
    }

    public function lessons($id)
    {
        $section = Section::with('lessons')->findOrFail($id);
        $lessons = $section->lessons()->get();
        return response()->json($lessons);
    }

    public function updateLessonsOrder(Request $request, $sectionId)
    {
        $section = Section::with('lessons')->findOrFail($sectionId);
        if (auth()->user()->id !== $section->course->instructor->user_id) {
            return response()->json('Unauthorized', 403);
        }

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|distinct',
        ]);

        $existingIds = $section->lessons->pluck('id')->toArray();
        $newOrder = $request->order;
        sort($existingIds);
        sort($newOrder);
        if ($existingIds !== $newOrder) {
            return response()->json(['error' => 'Order array must contain all lesson IDs.'], 422);
        }
        foreach ($request->order as $index => $lessonId) {
            Lesson::where('id', $lessonId)->update(['order' => $index + 1]);
        }

        return response()->json(['message' => 'Lessons reordered successfully']);
    }
}
