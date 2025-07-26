<?php

namespace App\Models\Satset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatsetErrorRespon extends Model
{
    use HasFactory;
    protected $table = 'satset_error_respon';
    protected $guarded = ['id'];
    protected $casts = [
        'response' => 'array'
    ];
}
