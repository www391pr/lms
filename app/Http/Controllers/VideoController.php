<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\CourseStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function getVideoLink($filename)
    {
        $student = auth()->user()->student ?? null;
        $lesson = Lesson::where('file_name', $filename)
            ->with('section.course')
            ->first();

        if (!$lesson) {
            return response()->json('Video not found', 404);
        }

        $section = $lesson->section;
        $course  = $section->course;

        // check if this lesson is in the first section of the course
        $firstSection = $course->sections()->orderBy('order', 'asc')->first();

        if ($firstSection && $section->id === $firstSection->id) {
            // check if the lesson is the first or second in this section
            $firstTwoLessons = $firstSection->lessons()->orderBy('order', 'asc')->take(2)->pluck('id')->toArray();

            if (in_array($lesson->id, $firstTwoLessons)) {
                return response()->json([
                    'url' => URL::signedRoute('stream.video', [
                        'filename' => $filename,
                    ], now()->addHours(3))
                ]);
            }
        }

        if($student) {
            $courseStudent = CourseStudent::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->first();
            if (!$courseStudent) {
                return response()->json('You have no access to this course', 403);
            }
            if ($courseStudent->status != 'enrolled' && $courseStudent->status != 'completed') {
                return response()->json('You have no access to this course', 403);
            }
        } else if (!$student && !auth()->user()->isAdmin()) {
           return response()->json('You have no access to this course', 403);
         }

        return response()->json([
            'url' => URL::signedRoute('stream.video', [
                'filename' => $filename,
            ], now()->addHours(3))
        ]);
    }
//    public function getVideoLink($filename)
//    {
//      $student = auth()->user()->student;
//      $lesson = Lesson::where('file_name', $filename)->with('section.course')->first();
//      if(!$lesson){
//          return response()->json('Video not found', 404);
//      }
//      $courseId = $lesson->section->course->id;
//      $courseStudent = CourseStudent::where('student_id', $student->id)->where('course_id', $courseId)->first();
//      if(!$courseStudent){
//          return response()->json('You have no access to this course', 403);
//      }
//      if($courseStudent->status != 'enrolled' && $courseStudent->status != 'completed'){
//          return response()->json('You have no access to this course', 403);
//      }
//
//        return response()->json([
//            'url' => URL::signedRoute('stream.video', [
//                'filename' => $filename,
//            ], now()->addHours(3))
//        ]);
//    }
    public function streamVideo(Request $request, $filename)
    {
        if (!$request->hasValidSignature()) {
            return response()->json('Invalid or expired link.', 403);
        }

        $path = storage_path("app/private/videos/" . $filename);
        if (!file_exists($path)) {
            abort(404);
        }
        $size = filesize($path);
        $start = 0;
        $end = $size - 1;

        if ($request->hasHeader('Range')) {
            if (preg_match('/bytes=(\d+)-(\d*)/', $request->header('Range'), $matches)) {
                $start = intval($matches[1]);
                $end = isset($matches[2]) && is_numeric($matches[2]) ? intval($matches[2]) : $end;
            }
            $length = $end - $start + 1;

            $headers = [
                'Content-Type' => 'video/mp4',
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$size",
                'Accept-Ranges' => 'bytes',
            ];

            return response()->stream(function () use ($path, $start, $length) {
                $handle = fopen($path, 'rb');
                fseek($handle, $start);
                while (!feof($handle) && $length > 0) {
                    $buffer = fread($handle, min(8192, $length));
                    echo $buffer;
                    $length -= strlen($buffer);
                }
                fclose($handle);
            }, 206, $headers);
        }

        return response()->stream(function () use ($path) {
            readfile($path);
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
        ]);
    }
    public function getSubtitlesLanguages($filename)
    {
        $basePath = storage_path("app/private/subtitles");
        $filePrefix = pathinfo($filename, PATHINFO_FILENAME);
        $languages = [];
        $files = scandir($basePath);
        foreach ($files as $file) {
            if (preg_match("/^{$filePrefix}-(.+)\.vtt$/", $file, $matches)) {
                $languages[] = $matches[1];
            }
        }

        return response()->json($languages);
    }
    public function getSubtitles($filename)
    {
        $path = storage_path("app/private/subtitles/{$filename}");

        if (!file_exists($path)) {
            return response()->json(['error' => 'Subtitle file not found'], 404);
        }

        return response()->file($path, [
            'Content-Type' => 'text/vtt'
        ]);
    }
    public function addSubtitles(Request $request, $id)
    {
        $lesson = Lesson::with('section.course')->findOrFail($id);

        if (auth()->user()->id !== $lesson->section->course->instructor->user_id) {
            return response()->json(['Unauthorized'], 403);
        }
        $request->validate([
            'subtitles'   => 'required|array',
            'subtitles.*' => 'file|max:10240',
        ]);
        foreach ($request->file('subtitles') as $idx => $file) {
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext != 'vtt') {
                return response()->json([
                    'message' => 'Only .vtt subtitle files are allowed.',
                ], 422);
            }
        }
        foreach ($request->file('subtitles') as $key => $subtitleFile) {
            if (!is_string($key) || strlen($key) !== 2) {
                return response()->json(['error' => "Invalid language code: $key"], 422);
            }
        }
        $filePrefix = pathinfo($lesson->file_name, PATHINFO_FILENAME);

        foreach ($request->file('subtitles') as $key => $subtitleFile) {
            $filename = "{$filePrefix}-{$key}.vtt";
            $subtitleFile->storeAs('subtitles', $filename);
        }

        return response()->json(['message' => 'Subtitles added successfully'], 201);
    }
    public function deleteSubtitles($id, $lang)
    {
        $lesson = Lesson::with('section.course')->findOrFail($id);
        if (auth()->user()->id !== $lesson->section->course->instructor->user_id) {
            return response()->json('Unauthorized', 403);
        }
        $filePrefix = pathinfo($lesson->file_name, PATHINFO_FILENAME);
        $filename = "{$filePrefix}-{$lang}.vtt";
        $path = "subtitles/{$filename}";
        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'Subtitle file not found'], 404);
        }
        Storage::disk('local')->delete($path);
        return response()->json(['message' => 'Subtitle deleted successfully']);
    }
}
