<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'student')->get();

        foreach ($students as $user) {
            Student::create([
                'user_id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
            ]);
        }
    }
}
