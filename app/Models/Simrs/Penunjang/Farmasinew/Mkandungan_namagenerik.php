<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mkandungan_namagenerik extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $connection = 'farmasi';
    protected $table = 'farmasi_kandungan';
    protected $guarded = ['id'];
}
