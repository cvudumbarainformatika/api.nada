<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Msigna extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $table = 'signa';
    protected $guarded = ['id'];
}
