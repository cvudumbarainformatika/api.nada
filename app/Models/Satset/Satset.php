<?php

namespace App\Models\Satset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satset extends Model
{
    use HasFactory;
    protected $table = 'satsets';
    protected $guarded = ['id'];
    protected $casts = [
        'response' => 'array'
    ];
}
