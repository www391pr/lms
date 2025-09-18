<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'instructor_id', 'amount', 'status', 'stripe_transfer_id', 'idempotency_key'
    ];

    public function instructor() {
        return $this->belongsTo(Instructor::class);
    }

    public function payouts()
    {
        return $this->hasMany(\App\Models\Payout::class);
    }
}
