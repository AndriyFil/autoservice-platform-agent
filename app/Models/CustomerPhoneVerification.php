<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPhoneVerification extends Model
{
    protected $fillable = [
        'phone_normalized',
        'code_hash',
        'expires_at',
        'attempts',
        'invalidated_at',
        'consumed_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'attempts' => 'integer',
            'invalidated_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
