<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Seeder;

class LessonsTableSeeder extends Seeder
{
    public function run(): void
    {
        $videoFiles = [
            'intro.mp4',
            'lesson_1.mp4',
            'lesson_2.mp4',
        ];

        foreach (Section::all() as $section) {
            foreach ($videoFiles as $index => $fileName) {
                Lesson::create([
                    'title' => ucfirst(str_replace('_', ' ', pathinfo($fileName, PATHINFO_FILENAME))) . " of Section {$section->id}",
                    'section_id' => $section->id,
                    'duration' => rand(120, 1200), // 2 to 20 minutes
                    'file_name' => $fileName,
                    'order' => $index + 1,
                    'views' => rand(0, 500),
                ]);
            }
        }
    }
}
