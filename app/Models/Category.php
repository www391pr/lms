<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
      'name',
      'parent_id',
      'image'
    ];

    public function students   (): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }
    public function children() : HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent() : BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    public function instructors():BelongsToMany
    {
        return $this->belongsToMany(Instructor::class);
    }
    public function courses():BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_category');
    }
}
