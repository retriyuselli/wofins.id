<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'need',
        'paket',
        'message',
        'status',
    ];
}
