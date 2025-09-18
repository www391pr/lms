<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonReport extends Model
{
    protected $fillable = ['student_id', 'lesson_id', 'message'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
