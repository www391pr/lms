<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Comment extends Model
{
    protected $fillable = ['lesson_id', 'student_id', 'body'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
