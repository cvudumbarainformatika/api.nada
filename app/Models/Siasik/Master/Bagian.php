<?php

namespace App\Models\Siasik\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bagian extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $table = 'm_bagian';
    protected $guarded = ['id'];
}
