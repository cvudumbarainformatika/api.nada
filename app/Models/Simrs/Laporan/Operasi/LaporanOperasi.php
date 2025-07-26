<?php

namespace App\Models\Simrs\Laporan\Operasi;

use App\Models\KunjunganPoli;
use App\Models\KunjunganRawatInap;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\RuanganRawatInap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanOperasi extends Model
{
    use HasFactory;
    protected $table = 'rs217';
    protected $guarded = ['id'];

    public function permintaanoper()
    {
        return $this->belongsTo(PermintaanOperasi::class, 'rs1', 'rs1');
    }

    public function kunjungan_poli()
    {
        return $this->belongsTo(KunjunganPoli::class, 'rs1', 'rs1');
    }
    public function kunjungan_rawat_inap()
    {
        return $this->belongsTo(KunjunganRawatInap::class, 'rs1', 'rs1');
    }

    public function poli()
    {
        return $this->belongsTo(Poli::class, 'rs23', 'rs1');
    }
    public function ruangan_rawat_inap()
    {
        return $this->belongsTo(RuanganRawatInap::class, 'rs23', 'rs4');
    }

    public function pasien_kunjungan_poli()
    {
        return $this->hasOneThrough(
            Pasien::class,
            KunjunganPoli::class,
            'rs1', // Foreign key on the kunjungan poli table...
            'rs1', // Foreign key on the pasien table...
            'rs1', // Local key on the transaksi laborat table...
            'rs2' // Local key on the pasien table...
        );
    }
    public function pasien_kunjungan_rawat_inap()
    {
        return $this->hasOneThrough(
            Pasien::class,
            KunjunganRawatInap::class,
            'rs1', // Foreign key on the kunjungan rawat inap table...
            'rs1', // Foreign key on the pasien table...
            'rs1', // Local key on the transaksi laborat table...
            'rs2' // Local key on the pasien table...
        );
    }

    public function kunjunganpasien()
    {
        return $this->hasOneThrough(
            Pasien::class,
            KunjunganRawatInap::class,
            KunjunganPoli::class,
            'rs1', // Foreign key on the kunjungan rawat inap table...
            'rs1', // Foreign key on the pasien table...
            'rs1', // Local key on the transaksi laborat table...
            'rs2' // Local key on the pasien table...
        );
    }


    public function permintaanoperasi()
    {
        return $this->belongsTo(PermintaanOperasi::class, 'rs1', 'rs1');
    }
}
