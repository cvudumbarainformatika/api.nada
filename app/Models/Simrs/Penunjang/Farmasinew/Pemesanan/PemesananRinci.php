<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemesananRinci extends Model
{
    use HasFactory;
    protected $table = 'pemesanan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    /**
     * catatan flag
     * flag 1 itu barang sudah datang semua
     * flag 2 pemesanan di tolak agar bisa di pesankan lagi
     */
    public function masterobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }

    public function pemesananheder()
    {
        return $this->hasOne(PemesananHeder::class, 'nopemesanan', 'nopemesanan');
    }
    public function rencanar()
    {
        return $this->hasMany(RencanabeliR::class, 'no_rencbeliobat', 'noperencanaan');
    }
    public function penerimaan()
    {
        return $this->hasMany(PenerimaanHeder::class, 'nopemesanan', 'nopemesanan');
    }
}
