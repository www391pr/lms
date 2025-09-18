<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = ['instructor_id' , 'code' , 'value' , 'expires_at' , 'is_active'];
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
