<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Instructor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstructorCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructors = Instructor::all();
        $mainCategories = Category::whereNull('parent_id')->get();

        foreach ($instructors as $instructor) {
            $selectedMain = $mainCategories->random(rand(1,  min(3, $mainCategories->count())));
            $instructor->categories()->attach($selectedMain->pluck('id'));
            foreach ($selectedMain as $main) {
                $subcategories = Category::where('parent_id', $main->id)->get();
                if ($subcategories->count() > 0) {
                    $selectedSub = $subcategories->random(rand(1, min(2, $subcategories->count())));
                    $instructor->categories()->attach($selectedSub->pluck('id'));
                }
            }
        }
    }
}
