<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructor extends Model
{
    protected $fillable = [
        'verified',
        'full_name',
        'views',
        'bio',
        'rating',
        'cv_path',
        'enabled',
        'current_balance',
        'total_balance',
        'stripe_account_id'
    ];
    protected $hidden = [
        'user_id',
        'created_at',
        'updated_at'
    ];
    protected $appends = ['avatar'];


    protected $casts = [
        'enabled' => 'boolean',
        'current_balance' => 'decimal:2',
        'total_balance' => 'decimal:2',
    ];
    public function getAvatarAttribute()
    {
        return $this->user ? asset($this->user->avatar) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function categories():BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
    public function courses():HasMany
    {
        return $this->hasMany(Course::class);
    }
    public function ratings():HasMany
    {
      return $this->hasMany(InstructorRating::class);
    }


    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }
}
