<?php

namespace App\Models\Siasik\Akuntansi\Sp3b;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sp3b_rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'sp3b_rinci';
    public $timestamps = false;
}
