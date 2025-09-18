<?php

namespace App\Http\Controllers;

use App\Events\SectionDurationUpdated;
use App\Models\CourseStudent;
use App\Models\Lesson;
use App\Models\LessonStudent;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;

class LessonController extends Controller
{

    public function store(Request $request, FFMpeg $ffmpeg)
    {
        $request->validate([
            'title' => 'required|string',
            'section_id' => 'required|exists:sections,id',
            'video' => 'required|file|mimes:mp4,mov,ogg,webm|max:512000',
        ]);

        $section = Section::findOrFail($request->section_id);

        // Check instructor authorization
        if ($section->course->instructor_id != auth()->user()->instructor->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Lesson::where('section_id', $section->id)->max('order') + 1;

        $path = $request->file('video')->store('videos');
        $filename = basename($path);

        $video = $ffmpeg->open(storage_path('app/private/' . $path));
        $duration = (int) $video->getFormat()->get('duration');

        $lesson = Lesson::create([
            'title' => $request->title,
            'section_id' => $section->id,
            'duration' => $duration,
            'file_name' => $filename,
            'order' => $order,
        ]);

        // update section total duration
        $section->total_duration = $section->lessons()->sum('duration');
        $section->save();

        // fire event to update course duration
        event(new SectionDurationUpdated($section));

        return response()->json($lesson, 201);
    }

//    public function store(Request $request, FFMpeg $ffmpeg)
//    {
//        $request->validate([
//            'title' => 'required|string',
//            'section_id' => 'required|exists:sections,id',
//            'video' => 'required|file|mimes:mp4,mov,ogg,webm|max:512000',
//        ]);
//        if(Section::find($request->section_id)->course->instructor_id!=auth()->user()->instructor->id)
//            return response()->json(['message' => 'Unauthorized'], 401);
//        $order = Lesson::where('section_id', $request->section_id)->max('order') + 1;
//        $path = $request->file('video')->store('videos');
//        $filename = basename($path);
//        $video = $ffmpeg->open(storage_path('app/private/' . $path));
//        $duration = (int)$video->getFormat()->get('duration');
//
//        $lesson = Lesson::create([
//            'title' => $request->title,
//            'section_id' => $request->section_id,
//            'duration' => $duration,
//            'file_name' => $filename,
//            'order' => $order,
//        ]);
//        return response()->json($lesson, 201);
//    }

    public function show($id)
    {
        $lesson = Lesson::with([
            'section.course.instructor:id,name,avatar'
        ])->find($id);

        if (!$lesson) {
            return response()->json(['message' => 'lesson not found'], 404);
        }

        return response()->json([
                'id' => $lesson->id,
                'title' => $lesson->title,
                'section_id' => $lesson->section_id,
                'file_name' => $lesson->file_name,
                'duration' => $lesson->duration,
                'instructor' => [
                    'id' => $lesson->section->course->instructor->id,
                    'name' => $lesson->section->course->instructor->full_name,
                    'avatar' => $lesson->section->course->instructor->avatar,
                ],
        ]);
    }

    public function update(Request $request, $id, FFMpeg $ffmpeg)
    {
        $lesson = Lesson::with('section.course')->findOrFail($id);
        if (auth()->user()->id !== $lesson->section->course->instructor->user_id) {
            return response()->json('Unauthorized', 403);
        }

        $request->validate([
            'title' => 'sometimes|string',
            'section_id' => 'sometimes|exists:sections,id',
            'video' => 'sometimes|file|mimes:mp4,mov,ogg,webm|max:512000',
        ]);

        $dataToUpdate = $request->only(['title', 'section_id']);

        if ($request->hasFile('video')) {
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

            $path = $request->file('video')->store('videos');
            $dataToUpdate['file_name']  = basename($path);
            $video = $ffmpeg->open(storage_path('app/private/' . $path));
            $dataToUpdate['duration']  = (int)$video->getFormat()->get('duration');
        }
        $lesson->update($dataToUpdate);

        $section = $lesson->section;
        $section->total_duration = $section->lessons()->sum('duration');
        $section->save();

        event(new SectionDurationUpdated($section));

        return response()->json($lesson);
    }

    public function destroy($id)
    {
        $lesson = Lesson::with('section.course')->findOrFail($id);
        if (auth()->user()->id !== $lesson->section->course->instructor->user_id) {
            return response()->json('Unauthorized', 403);
        }
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

        $section = $lesson->section;
        $deletedOrder = $lesson->order;

        DB::transaction(function () use ($lesson, $deletedOrder) {
            $lesson->delete();

            Lesson::where('section_id', $lesson->section_id)
                ->where('order', '>', $deletedOrder)
                ->decrement('order');
        });

        $section->total_duration = $section->lessons()->sum('duration');
        $section->save();

        event(new SectionDurationUpdated($section));

        return response()->json(['message' => 'Lesson deleted']);
    }

    public function completeLesson($lessonId)
    {
        $student = auth()->user()->student;
        $lesson = Lesson::findOrFail($lessonId);
        $alreadyCompleted = LessonStudent::where('lesson_id', $lessonId)
            ->where('student_id', $student->id)
            ->exists();

       if ($alreadyCompleted) {
           return response()->json(['message' => 'This lesson has already been completed.'], 200);
       }

        LessonStudent::create([
            'lesson_id' => $lessonId,
            'student_id' => $student->id,
        ]);

        $course = $lesson->section->course()->with('sections.lessons')->first();
        $allLessons = $course->sections->flatMap->lessons;

        $completedLessonsCount = LessonStudent::whereIn('lesson_id', $allLessons->pluck('id'))
            ->where('student_id', $student->id)
            ->count();
        if ($completedLessonsCount == $allLessons->count()) {
            CourseStudent::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->update(['status' => 'completed']);
        }

        return response()->json(['message' => 'Lesson marked as completed successfully.'], 201);
    }
}
