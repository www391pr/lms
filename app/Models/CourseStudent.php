<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseStudent extends Model
{
    protected $fillable = ['student_id', 'course_id', 'rating', 'status'];
    protected $table = 'course_student';
    public function course():BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

}
