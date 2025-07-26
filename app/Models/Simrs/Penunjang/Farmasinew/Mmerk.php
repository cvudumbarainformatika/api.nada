<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mmerk extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'mmerk';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
