<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonStudent extends Model
{
    protected $table = 'lesson_student'; // since it's not plural

    protected $fillable = [
        'lesson_id',
        'student_id',
    ];

    public $timestamps = true;

    // Optional relationships
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }}
