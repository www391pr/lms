<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programming = Category::create([
            'name' => 'Programming',
            'image' => 'programming.png',
        ]);

        $business = Category::create([
            'name' => 'Business',
            'image' => 'business.png',
        ]);

        $design = Category::create([
            'name' => 'Design',
            'image' => 'design.png',
        ]);

        // Subcategories for Programming
        $this->createSubCategories($programming->id, [
            'Web Development',
            'Mobile Apps',
            'Game Development',
            'Data Science'
        ]);

        // Subcategories for Business
        $this->createSubCategories($business->id, [
            'Marketing',
            'Finance',
            'Entrepreneurship',
            'Management'
        ]);

        // Subcategories for Design
        $this->createSubCategories($design->id, [
            'Graphic Design',
            'UI/UX Design',
            '3D Modeling',
            'Motion Graphics'
        ]);
    }

    protected function createSubCategories($parentId, array $subNames)
    {
        foreach ($subNames as $name) {
            Category::create([
                'name' => $name,
                'parent_id' => $parentId,
                'image' => strtolower(str_replace(' ', '_', $name)) . '.png',
            ]);
        }
    }
}
