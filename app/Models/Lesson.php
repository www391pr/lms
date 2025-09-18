<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lesson extends Model
{
    protected $fillable = ['title', 'section_id', 'duration', 'views', 'file_name', 'order'];

    public  function section(): BelongsTo{
        return $this->belongsTo(Section::class);
    }
    public function students() : BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
