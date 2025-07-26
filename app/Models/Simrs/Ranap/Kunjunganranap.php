<?php

namespace App\Models\Simrs\Ranap;

use App\Models\KunjunganPoli;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Covid\StatusCovid;
use App\Models\Simrs\DischargePlanning\DischargePlanning;
use App\Models\Simrs\DischargePlanning\SkriningPulang;
use App\Models\Simrs\DischargePlanning\SummaryPulang;
use App\Models\Simrs\Edukasi\ImplementasiEdukasi;
use App\Models\Simrs\Edukasi\Transedukasi;
use App\Models\Simrs\Ews\KeteranganTindakan;
use App\Models\Simrs\Ews\ProcedureM;
use App\Models\Simrs\Hais\HaisTrans;
use App\Models\Simrs\InformConcern\InformConcern;
use App\Models\Simrs\Kasir\Biayamaterai;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Laporan\Operasi\PermintaanOperasi;
use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\MkamarRanap;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mruangan;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosagizi;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakebidanan;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakeperawatan;
use App\Models\Simrs\Pelayanan\DokumenUpload;
use App\Models\Simrs\Pelayanan\PraAnastesi;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Penjaminan\GroupingRanap;
use App\Models\Simrs\Penjaminan\Klaimranap;
use App\Models\Simrs\Penunjang\Ambulan\Ambulan;
use App\Models\Simrs\Penunjang\Ambulan\ReqAmbulan;
use App\Models\Simrs\Penunjang\Apheresis\PermintaanApheresis;
use App\Models\Simrs\Penunjang\Bankdarah\PermintaanBankdarah;
use App\Models\Simrs\Penunjang\Bdrs\Bdrstrans;
use App\Models\Simrs\Penunjang\Cathlab\ReqCathlab;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajal;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanrinci;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanrincilalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranap;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplaluracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplaluracikanrinci;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranapracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranapracikanrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use App\Models\Simrs\Penunjang\Gizi\AsuhanGizi;
use App\Models\Simrs\Penunjang\Hemodialisa\Intradialitik;
use App\Models\Simrs\Penunjang\Hemodialisa\PengkajianHemodialisa;
use App\Models\Simrs\Penunjang\Kamarjenazah\KamarjenazahPermintaan;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasiigd;
use App\Models\Simrs\Penunjang\Keperawatan\Keperawatan;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Lain\Lain;
use App\Models\Simrs\Penunjang\Oksigen\Oksigen;
use App\Models\Simrs\Penunjang\Operasi\PermintaanOperasiIrd;
use App\Models\Simrs\Penunjang\PenunjangKeluar\PenunjangKeluar;
use App\Models\Simrs\Penunjang\Radiologi\PembacaanradiologiController;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Planing\Plannindokter;
use App\Models\Simrs\Planing\Planningdokter;
use App\Models\Simrs\Psikologitrans\Psikologitrans;
use App\Models\Simrs\Rajal\Memodiagnosadokter;
use App\Models\Simrs\Ranap\Pelayanan\Cppt;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjunganranap extends Model
{
    use HasFactory;
    protected $table = 'rs23';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';
    protected $connection = 'mysql';
    //protected $connection = 'farmasi';

    // ini dipake hd
    public function pegsim()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kdpeg');
    }
    public function statuscovid()
    {
        return $this->hasMany(StatusCovid::class, 'noreg', 'noreg');
    }
    public function dataigd()
    {
        return $this->hasOne(KunjunganPoli::class, 'rs1', 'rs1');
    }
    public function relmasterruangranap()
    {
        return $this->hasOne(Mruangranap::class, 'rs1', 'rs5');
    }

    public function ruangtitipan()
    {
        return $this->hasOne(Mruangranap::class, 'rs1', 'titipan');
    }

    public function relsistembayar()
    {
        return $this->hasOne(Msistembayar::class, 'rs1', 'rs19');
    }

    public function reldokter()
    {
        return $this->hasOne(Dokter::class, 'rs1', 'rs10');
    }

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    public function rstigalimax()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1');
    }
    public function rstigalimaxx()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1');
    }

    public function akomodasikamar()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1');
    }

    public function biayamaterai()
    {
        return $this->hasMany(Biayamaterai::class, 'rs1', 'rs1');
    }

    public function tindakandokter()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function visiteumum()
    {
        return $this->hasMany(Visite::class, 'rs1', 'rs1');
    }

    public function tindakanperawat()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function asuhangizi()
    {
        return $this->hasMany(AsuhanGizi::class, 'rs1', 'rs1');
    }

    public function makanpasien()
    {
        return $this->hasMany(AsuhanGizi::class, 'rs1', 'rs1');
    }

    public function oksigen()
    {
        return $this->hasMany(Oksigen::class, 'rs1', 'rs1');
    }

    public function keperawatan()
    {
        return $this->hasMany(Keperawatan::class, 'rs1', 'rs1');
    }

    public function laborat()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function laboratold()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function transradiologi()
    {
        return $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    public function tindakanendoscopy()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function kamaroperasiibs()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1', 'rs1');
    }

    public function tindakanoperasi()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakanoperasiigd()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function kamaroperasiibsx()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1', 'rs1');
    }

    public function tindakanoperasix()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function kamaroperasiigd()
    {
        return $this->hasMany(Kamaroperasiigd::class, 'rs1', 'rs1');
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

    public function tindakancardio()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakaneeg()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function psikologtransumum()
    {
        return $this->hasMany(Psikologitrans::class, 'rs1', 'rs1');
    }

    public function bdrs()
    {
        return $this->hasMany(Bdrstrans::class, 'rs1', 'rs1');
    }

    public function penunjangkeluar()
    {
        return $this->hasMany(PenunjangKeluar::class, 'noreg', 'rs1');
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

    public function apotekranapx()
    {
        return $this->hasMany(Apotekranap::class, 'rs1', 'rs1');
    }

    public function apotekranaplalux()
    {
        return $this->hasMany(Apotekranaplalu::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanhederx()
    {
        return $this->hasMany(Apotekranapracikanheder::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanrincix()
    {
        return $this->hasMany(Apotekranapracikanrinci::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanhederlalux()
    {
        return $this->hasMany(Apotekranaplaluracikanheder::class, 'rs1', 'rs1');
    }

    public function apotekranapracikanrincilalux()
    {
        return $this->hasMany(Apotekranaplaluracikanrinci::class, 'rs1', 'rs1');
    }

    public function rstigalimaxxx()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1');
    }

    public function irdtindakan()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function irdlaborat()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function irdtransradiologi()
    {
        return $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    public function irdtindakanoperasix()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function irdkamaroperasiigd()
    {
        return $this->hasMany(Kamaroperasiigd::class, 'rs1', 'rs1');
    }

    public function irdbdrs()
    {
        return $this->hasMany(Bdrstrans::class, 'rs1', 'rs1');
    }

    public function irdbiayamaterai()
    {
        return $this->hasMany(Biayamaterai::class, 'rs1', 'rs1');
    }

    public function ambulan()
    {
        return $this->hasMany(Ambulan::class, 'rs1', 'rs1');
    }

    public function irdambulan()
    {
        return $this->hasMany(Ambulan::class, 'rs1', 'rs1');
    }

    public function irdtindakanhd()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function irdtindakananastesidiluarokdanicu()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function irdtindakanfisioterapi()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function groupingranap()
    {
        return $this->hasMany(GroupingRanap::class, 'noreg', 'rs1');
    }

    public function laboratdiird()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function transradiologidiird()
    {
        return $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    public function anamnesis()
    {
        return $this->hasMany(Anamnesis::class, 'rs1', 'rs1');
    }
    public function anamnesisAwalHd()
    {
        return $this->hasMany(Anamnesis::class, 'rs2', 'norm');
    }
    public function pemeriksaan()
    {
        return $this->hasMany(PemeriksaanUmum::class, 'rs1', 'rs1');
    }
    public function pemeriksaanAwalHd()
    {
        return $this->hasMany(PemeriksaanUmum::class, 'rs2', 'norm');
    }
    public function penilaian()
    {
        return $this->hasMany(Penilaian::class, 'rs1', 'rs1');
    }

    public function diagnosamedis()
    {
        return $this->hasMany(Diagnosa::class, 'rs1', 'rs1');
    }

    public function diagnosakeperawatan()
    {
        return $this->hasMany(Diagnosakeperawatan::class, 'noreg', 'rs1');
    }
    public function diagnosakeperawatanAwalHd()
    {
        return $this->hasMany(Diagnosakeperawatan::class, 'norm', 'norm');
    }
    public function diagnosakebidanan()
    {
        return $this->hasMany(Diagnosakebidanan::class, 'noreg', 'rs1');
    }
    public function diagnosagizi()
    {
        return $this->hasMany(Diagnosagizi::class, 'noreg', 'rs1');
    }
    public function konsultasi()
    {
        return $this->hasMany(Konsultasi::class, 'noreg', 'rs1');
    }
    public function diagnosa()
    {
        return $this->hasMany(Diagnosa::class, 'rs1', 'rs1');
    }

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function radiologi()
    {
        return $this->hasMany(Transpermintaanradiologi::class, 'rs1', 'rs1');
    }

    public function fisio()
    {
        return $this->hasMany(Fisioterapipermintaan::class, 'rs1', 'rs1');
    }

    public function operasi()
    {
        return $this->hasMany(PermintaanOperasi::class, 'rs1', 'rs1');
    }
    public function operasi_ird()
    {
        return $this->hasMany(PermintaanOperasiIrd::class, 'rs1', 'rs1');
    }
    public function bankdarah()
    {
        return $this->hasMany(PermintaanBankdarah::class, 'rs1', 'rs1');
    }
    public function apheresis()
    {
        return $this->hasMany(PermintaanApheresis::class, 'noreg', 'rs1');
    }
    public function cathlab()
    {
        return $this->hasMany(ReqCathlab::class, 'noreg', 'rs1');
    }
    public function permintaanambulan()
    {
        return $this->hasMany(ReqAmbulan::class, 'rs1', 'rs1');
    }

    public function penunjanglain()
    {
        return $this->hasMany(Lain::class, 'rs1', 'rs1');
    }
    public function perawatanjenazah()
    {
        return $this->hasMany(KamarjenazahPermintaan::class, 'rs1', 'rs1');
    }
    public function hais()
    {
        return $this->hasMany(HaisTrans::class, 'noreg', 'rs1');
    }
    public function cppt()
    {
        return $this->hasMany(Cppt::class, 'noreg', 'rs1');
    }
    public function edukasi()
    {
        return $this->hasMany(Transedukasi::class, 'rs1', 'rs1');
    }



    public function hasilradiologi()
    {
        return $this->hasMany(PembacaanradiologiController::class, 'rs1', 'rs1');
    }

    public function klaimranap()
    {
        return $this->hasOne(Klaimranap::class, 'noreg', 'rs1');
    }

    public function apotekrajal()
    {
        return $this->hasMany(Apotekrajal::class, 'rs1', 'rs1');
    }

    public function apotekrajalpolilalu()
    {
        return $this->hasMany(Apotekrajallalu::class, 'rs1', 'rs1');
    }

    public function apotekracikanrajallalu()
    {
        return $this->hasManyThrough(
            Apotekrajalracikanrincilalu::class,
            Apotekrajalracikanhedlalu::class,
            'rs1',
            'rs1'
        );
    }

    public function apotekracikanrajal()
    {
        return $this->hasManyThrough(
            Apotekrajalracikanrinci::class,
            Apotekrajalracikanheder::class,
            'rs1',
            'rs1'
        );
    }

    public function pemeriksaanfisik()
    {
        return $this->hasMany(Pemeriksaanfisik::class, 'rs1', 'rs1');
    }

    public function newapotekrajal()
    {
        // return $this->hasOne(Resepkeluarheder::class, 'noreg', 'rs1');
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'noreg');
    }

    public function laborats()
    {
        return $this->hasMany(LaboratMeta::class, 'noreg', 'rs1');
    }
    //pegawai dari simpeg
    public function pegawai()
    {
        return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'rs10');
    }
    public function dokumenluar()
    {
        return $this->hasMany(DokumenUpload::class, 'noreg', 'rs1');
    }
    public function informconcern()
    {
        return $this->hasMany(InformConcern::class, 'noreg', 'rs1');
    }
    public function dischargeplanning()
    {
        return $this->hasMany(DischargePlanning::class, 'rs1', 'rs1');
    }
    public function skriningdischargeplannings()
    {
        return $this->hasMany(SkriningPulang::class, 'rs1', 'rs1');
    }
    public function summarydischargeplannings()
    {
        return $this->hasMany(SummaryPulang::class, 'rs1', 'rs1');
    }
    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'rs10');
    }
    public function kamaroperasi()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1', 'rs1');
    }
    public function laboratnonpaket()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function newfarmasi()
    {
        // return $this->hasOne(Resepkeluarheder::class, 'noreg', 'rs1');
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }

    public function newfarmasiracikan()
    {
        // return $this->hasOne(Resepkeluarheder::class, 'noreg', 'rs1');
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }
    public function kamarranap()
    {
        return $this->belongsTo(MkamarRanap::class, 'rs6', 'rs1');
    }

    public function sepranap()
    {
        return $this->hasOne(Sepranap::class, 'rs1', 'rs1');
    }

    public function memo()
    {
        return $this->hasOne(Memodiagnosadokter::class, 'noreg', 'rs1');
    }
    public function manymemo()
    {
        return $this->hasMany(Memodiagnosadokter::class, 'noreg', 'rs1');
    }
    public function procedure()
    {
        return $this->hasMany(ProcedureM::class, 'noreg', 'rs1');
    }
    public function keterangantindakan()
    {
        return $this->hasMany(KeteranganTindakan::class, 'noreg', 'rs1');
    }
    public function planningdokter()
    {
        return $this->hasOne(Planningdokter::class, 'noreg', 'rs1');
    }
    public function praanastesi()
    {
        return $this->hasMany(PraAnastesi::class, 'noreg', 'rs1');
    }
    // hd
    public function intradialitik()
    {
        return $this->hasMany(Intradialitik::class, 'rs1', 'noreg');
    }
    public function pengkajian()
    {
        return $this->hasMany(PengkajianHemodialisa::class, 'rs1', 'noreg');
    }

    public function rs239_implementasi()
    {
        return $this->hasMany(ImplementasiEdukasi::class, 'noreg', 'rs1');
    }
}
