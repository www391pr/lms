<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\Course; // make sure Course model exists

class SectionsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Get all existing courses
        $courses = Course::all();

        foreach ($courses as $course) {
            foreach (range(1, rand(2, 5)) as $i) {
                Section::create([
                    'title'     => "Section $i of Course {$course->id}",
                    'course_id' => $course->id,
                    'order'     => $i,
                ]);
            }
        }
    }
}
