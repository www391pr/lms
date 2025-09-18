<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstructorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructors = User::where('role', 'instructor')->get();

        foreach ($instructors as $user) {
            Instructor::create([
                'user_id' => $user->id,
                'verified' => true,
                'full_name' =>  $user->first_name . ' ' . $user->last_name,
                'views' => rand(0, 100),
                'bio' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.',
                'rating' => rand(0, 5),
            ]);
        }
    }
}