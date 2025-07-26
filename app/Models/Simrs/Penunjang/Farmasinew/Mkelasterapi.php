<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mkelasterapi extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'mkelasterapi';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
