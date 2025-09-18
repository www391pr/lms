<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    const ROLE_STUDENT = 'student';
    const ROLE_INSTRUCTOR = 'instructor';
    const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'first_name',
        'last_name',
        'user_name',
        'email',
        'password',
        'role',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function student() : HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function setRoleAttribute($value)
    {
        $this->attributes['role'] = strtolower($value);
    }

    public function isStudent()
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isInstructor()
    {
        return $this->role === self::ROLE_INSTRUCTOR;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }
    public function instructor() : HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    public function fcmTokens()
    {
        return $this->hasMany(\App\Models\FcmToken::class);
    }

}
