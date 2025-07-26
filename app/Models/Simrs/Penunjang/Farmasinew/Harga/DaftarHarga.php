<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Harga;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaftarHarga extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $guarded = ['id'];
}
