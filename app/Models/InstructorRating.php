<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorRating extends Model
{
    protected $fillable = ['student_id', 'instructor_id', 'rating'];
    
    public function instructor():BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
