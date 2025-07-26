<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapingkelasterapi extends Model
{
    use HasFactory;
    protected $table = 'mapingkelasterapi';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
