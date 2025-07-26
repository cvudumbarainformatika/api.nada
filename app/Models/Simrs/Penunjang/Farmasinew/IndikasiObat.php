<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndikasiObat extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
