<?php

namespace App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanKebidanan extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs253_kebidanan';
    protected $guarded = ['id'];
    
}
