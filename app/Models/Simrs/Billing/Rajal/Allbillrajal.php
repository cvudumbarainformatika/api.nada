<?php

namespace App\Models\Simrs\Billing\Rajal;

use App\Models\Antrean\Dokter;
use App\Models\Simrs\Kasir\Biayamaterai;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Master\Dokter as MasterDokter;
use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penjaminan\GruopingRajal;
use App\Models\Simrs\Penjaminan\Klaimrajal;
use App\Models\Simrs\Penunjang\Ambulan\Ambulan;
use App\Models\Simrs\Penunjang\Bdrs\Bdrstrans;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanrincilalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranap;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplaluracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplaluracikanrinci;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranapracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranapracikanrinci;
use App\Models\Simrs\Penunjang\Kamarjenazah\Kamarjenasahinap;
use App\Models\Simrs\Penunjang\Kamarjenazah\Kamarjenasahtrans;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Okigd\Okigdtrans;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Psikologitrans\Psikologitrans;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Allbillrajal extends Model
{
    use HasFactory;
    protected $table = 'rs17';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function masterpasien()
    {
        return $this->hasMany(Mpasien::class, 'rs1', 'rs2');
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs8', 'rs1');
    }

    public function msistembayar()
    {
        return $this->belongsTo(Msistembayar::class, 'rs14', 'rs1');
    }

    public function apotekrajalpolilaluumum()
    {
        return $this->hasMany(Apotekrajallalu::class, 'rs1', 'rs1');
    }

    public function apotekracikanrajalumum()
    {
        return $this->hasManyThrough(
            Apotekrajalracikanrincilalu::class,
            Apotekrajalracikanhedlalu::class,
            'rs1',
            'rs1'
        );
    }
    public function apotekrajalpolilalu()
    {
        return $this->hasMany(Apotekrajallalu::class, 'rs1', 'rs1');
    }

    public function apotekracikanrajal()
    {
        return $this->hasManyThrough(
            Apotekrajalracikanrincilalu::class,
            Apotekrajalracikanhedlalu::class,
            'rs1',
            'rs1'
        );
    }

    public function laborat()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function radiologi()
    {
        return $this->hasMany(Transpermintaanradiologi::class, 'rs1', 'rs1');
    }

    // public function radiologi()
    // {
    //     return $this->hasManyThrough(
    //         Transradiologi::class,
    //         Transpermintaanradiologi::class,
    //         'rs1','rs1'
    //     );
    // }

    public function dokter()
    {
        return $this->hasOne(MasterDokter::class, 'rs1', 'rs9');
    }

    public function rekammdedikumum()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function tindakanpoliumum()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function visiteumum()
    {
        return $this->hasMany(Visite::class, 'rs1', 'rs1');
    }

    public function psikologtransumum()
    {
        return $this->hasMany(Psikologitrans::class, 'rs1', 'rs1');
    }

    public function pendapatanumum()
    {
        return $this->hasMany(Kwitansilog::class, 'noreg', 'rs1');
    }

    public function pendapatanallbpjsx()
    {
        return $this->hasMany(Klaimrajal::class, 'noreg', 'rs1');
    }

    public function biayarekammedik()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function biayakartuidentitas()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function biayapelayananpoli()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function biayakonsulantarpoli()
    {
        return $this->hasMany(Pembayaran::class, 'rs1', 'rs1');
    }

    public function tindakanall()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function kamaroperasi()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1', 'rs1');
    }

    public function tindakanoperasi()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakanfisioterapi()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakanhd()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakananastesidiluarokdanicu()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakanendoscopy()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakandokterperawat()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakancardio()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakaneeg()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function administrasiigd()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1');
    }

    public function bdrs()
    {
        return $this->hasMany(Bdrstrans::class, 'rs1', 'rs1');
    }

    public function okigd()
    {
        return $this->hasMany(Okigdtrans::class, 'rs1', 'rs1');
    }

    public function tindakokigd()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function kamarjenasah()
    {
        return $this->hasMany(Kamarjenasahtrans::class, 'rs1', 'rs1');
    }

    public function kamarjenasahinap()
    {
        return $this->hasMany(Kamarjenasahinap::class, 'rs1', 'rs1');
    }

    public function ambulan()
    {
        return $this->hasMany(Ambulan::class, 'rs1', 'rs1');
    }

    public function apotekranap()
    {
        return $this->hasMany(Apotekranap::class, 'rs1', 'rs1');
    }

    public function apotekranaplalu()
    {
        return $this->hasMany(Apotekranaplalu::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanheder()
    {
        return $this->hasMany(Apotekranapracikanheder::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanrinci()
    {
        return $this->hasMany(Apotekranapracikanrinci::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanhederlalu()
    {
        return $this->hasMany(Apotekranaplaluracikanheder::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanrincilalu()
    {
        return $this->hasMany(Apotekranaplaluracikanrinci::class, 'rs1', 'rs1');
    }

    public function transradiologi()
    {
        return $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    public function biayamaterai()
    {
        return $this->hasMany(Biayamaterai::class, 'rs1', 'rs1');
    }

    public function pendapatanallbpjs()
    {
        return $this->hasMany(GruopingRajal::class, 'noreg', 'rs1');
    }

    public function klaimrajal()
    {
        return $this->hasOne(Klaimrajal::class, 'noreg', 'rs1');
    }
}
