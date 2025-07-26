<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returpenjualan_h extends Model
{
    use HasFactory;
    protected $table = 'retur_penjualan_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rinci()
    {
        return $this->hasMany(Returpenjualan_r::class, 'noretur', 'noretur');
    }
}
