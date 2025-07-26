<?php

namespace App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanPediatrik extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs253_pediatrik';
    protected $guarded = ['id'];
    protected $casts = [
        'glasgow' => 'array',
    ];


    

    
}
