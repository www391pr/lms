<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;

class CategoryStudentTableSeeder extends Seeder
{
    public function run()
    {
        $students = Student::all();

        $mainCategories = Category::whereNull('parent_id')->get();

        foreach ($students as $student) {
            $selectedMain = $mainCategories->random(rand(1, 2));
            $student->categories()->attach($selectedMain->pluck('id'));

            foreach ($selectedMain as $main) {
                $subcategories = Category::where('parent_id', $main->id)->get();

                if ($subcategories->count() > 0) {
                    $selectedSub = $subcategories->random(rand(1, min(3, $subcategories->count())));
                    $student->categories()->attach($selectedSub->pluck('id'));
                }
            }
        }
    }
}
