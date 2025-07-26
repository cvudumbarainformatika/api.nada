<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mbentuksediaan extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $connection = 'farmasi';
    protected $table = 'mbentuksediaan';
    protected $guarded = ['id'];
}
