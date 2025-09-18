<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['instructor_id', 'title', 'description', 'price', 'level', 'image', 'views', 'discount', 'rating', 'enabled'];

    public function instructor():BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }
    public function categories():BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'course_category');
    }
    public function ratings():HasMany
    {
        return $this->hasMany(CourseStudent::class);
    }
    public function sections():HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }
    public function reviews():HasMany
    {
        return $this->hasMany(CourseReview::class);
    }
    public function students():HasMany
    {
        return $this->hasMany(CourseStudent::class);
    }
//    public function lessons():HasMany
//    {
//        return $this->hasMany(Lesson::class);
//    }
}
