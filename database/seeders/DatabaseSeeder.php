<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            UsersTableSeeder::class,
            StudentsTableSeeder::class,
            CategoriesTableSeeder::class,
            CategoryStudentTableSeeder::class,
            InstructorsTableSeeder::class,
            InstructorCategoryTableSeeder::class,
            CoursesTableSeeder::class,
            SectionsTableSeeder::class,
            LessonsTableSeeder::class,
        ]);
    }
}
