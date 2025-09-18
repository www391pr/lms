<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Student extends Model
{

    protected $fillable =[
      'user_id',
      'full_name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'user_id',

    ];
    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_student')
            ->withPivot('status')
            ->withTimestamps();
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
