<?php

namespace App\Models\Satset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatsetToken extends Model
{
    use HasFactory;
    protected $table = 'satset_token';
    protected $guarded = ['id'];
}
