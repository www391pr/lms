<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CourseReview extends Model
{
    protected $fillable = ['student_id', 'course_id', 'review'];

    public function course():BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    public function student()
{
    return $this->belongsTo(Student::class);
}
}
