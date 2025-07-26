<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mjenisprodukx extends Model
{
    use HasFactory;
    protected $table = 'mjenisproduk';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
