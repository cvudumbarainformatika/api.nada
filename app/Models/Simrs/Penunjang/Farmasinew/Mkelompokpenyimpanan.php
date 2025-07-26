<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mkelompokpenyimpanan extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mkelompokpenyimpanan';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
