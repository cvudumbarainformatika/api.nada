<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sistembayarlain extends Model
{
    use HasFactory;
    protected $table = 'sistem_bayar_lain';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    
}
