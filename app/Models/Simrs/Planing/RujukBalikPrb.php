<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RujukBalikPrb extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'form' => 'array',
        'bpjs_response' => 'array'
    ];
}
