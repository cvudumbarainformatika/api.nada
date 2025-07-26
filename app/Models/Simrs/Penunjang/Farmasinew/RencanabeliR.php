<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RencanabeliR extends Model
{
    use HasFactory;
    protected $table = 'perencana_pebelian_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    /**
     * catatan flag
     * flag 1 itu rencanan selesai atau di anggap selesai sehingga tidak muncul di pemesanan
     */
    public function rincian()
    {
        return $this->hasOne(RencanabeliH::class, 'no_rencbeliobat', 'no_rencbeliobat');
    }

    public function mobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
    public function stok()
    {
        return $this->hasMany(Stokrel::class, 'kdobat', 'kdobat');
    }
    public function harga()
    {
        return $this->hasOne(DaftarHarga::class, 'kd_obat', 'kdobat')->orderBy('tgl_mulai_berlaku', 'DESC');
    }
    public function minmax()
    {
        return $this->hasMany(Mminmaxobat::class, 'kd_obat', 'kdobat');
    }
    public function penerimaan()
    {
        return $this->hasMany(PenerimaanRinci::class, 'kdobat', 'kdobat');
    }
    public function pesanan()
    {
        return $this->hasMany(PemesananRinci::class, 'noperencanaan', 'no_rencbeliobat');
    }
}
