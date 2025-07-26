<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penjualan;

use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganPenjualan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $casts = [
        'keterangan' => 'array',
    ];

    public function apotek()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'noreg');
    }
    public function rincian()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'noreg', 'noreg');
    }
}
