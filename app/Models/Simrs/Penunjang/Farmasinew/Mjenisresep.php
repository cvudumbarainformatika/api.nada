<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mjenisresep extends Model
{
    use HasFactory;
    protected $table = 'jenisresep';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
