<?php

namespace App\Models\Simrs\Rajal;

use App\Http\Controllers\Api\Simrs\Igd\RencanaTerapiDokterController;
use App\Models\Pasien;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\AnamnesisKebidanan;
use App\Models\Simrs\Anamnesis\HistoryKehamilan;
use App\Models\Simrs\Anamnesis\HistoryPerkawinan;
use App\Models\Simrs\Edukasi\ImplementasiEdukasi;
use App\Models\Simrs\Edukasi\Transedukasi;
use App\Models\Simrs\Ews\ProcedureM;
use App\Models\Simrs\Generalconsent\Generalconsent;
use App\Models\Simrs\jenazah\billjenazah;
use App\Models\Simrs\Kasir\Karcis;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosagizi;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakebidanan;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakeperawatan;
use App\Models\Simrs\Pelayanan\DokumenUpload;
use App\Models\Simrs\Pelayanan\Kandungan;
use App\Models\Simrs\Pelayanan\LaporanTindakan;
use App\Models\Simrs\Pelayanan\NeonatusKeperawatan;
use App\Models\Simrs\Pelayanan\NeonatusMedis;
use App\Models\Simrs\Pelayanan\Pediatri;
use App\Models\Simrs\Pelayanan\PraAnastesi;
use App\Models\Simrs\Pelayanan\PsikiatriPoli;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use App\Models\Simrs\Pemeriksaanfisik\Simpangambarpemeriksaanfisik;
use App\Models\Simrs\PemeriksaanRMkhusus\Polimata;
use App\Models\Simrs\Pendaftaran\Mgeneralconsent;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianambil;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Pendaftaran\Rajalumum\Taskidantrian;
use App\Models\Simrs\Penjaminan\listcasmixrajal;
use App\Models\Simrs\Penunjang\Ambulan\Ambulan;
use App\Models\Simrs\Penunjang\Ambulan\ReqAmbulan;
use App\Models\Simrs\Penunjang\Bankdarah\PermintaanBankdarah;
use App\Models\Simrs\Penunjang\Bdrs\Bdrstrans;
use App\Models\Simrs\Penunjang\DietTrans;
use App\Models\Simrs\Penunjang\Eeg\Eegtrans;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajal;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanrinci;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanrincilalu;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Farmasinew\TelaahResep;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use App\Models\Simrs\Penunjang\Hemodialisa\Intradialitik;
use App\Models\Simrs\Penunjang\Hemodialisa\PengkajianHemodialisa;
use App\Models\Simrs\Penunjang\Kamarjenazah\Kamarjenasahtrans;
use App\Models\Simrs\Penunjang\Kamarjenazah\KamarjenazahPermintaan;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\PermintaanOperasi;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Lain\Lain;
use App\Models\Simrs\Penunjang\Radiologi\PembacaanradiologiController;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Planing\Planing_Igd_Lama;
use App\Models\Simrs\Planing\Simpansuratkontrol;
use App\Models\Simrs\Planing\SkalaTransferIgd;
use App\Models\Simrs\Rajal\Igd\PemberianObatIgd;
use App\Models\Simrs\Rajal\Igd\RencanaTerapiDokter;
use App\Models\Simrs\Rajal\Igd\Tinjauan_ulang;
use App\Models\Simrs\Rajal\Igd\TriageA;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use App\Models\Simrs\Ranap\Rs141;
use App\Models\Simrs\Rekom\Rekomdpjp;
use App\Models\Simrs\Sharing\SharingTrans;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class KunjunganPoli extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs17';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    // ini dipake hd
    public function pegsim()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kdpeg');
    }
    public function manymemo()
    {
        return $this->hasMany(Memodiagnosadokter::class, 'noreg', 'rs1');
    }
    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    public function triage()
    {
        return $this->hasmany(TriageA::class, 'rs1', 'rs1');
    }

    public function praanastesi()
    {
        return $this->hasMany(PraAnastesi::class, 'noreg', 'rs1');
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs8', 'rs1');
    }

    public function msistembayar()
    {
        return $this->belongsTo(Msistembayar::class, 'rs14', 'rs1');
    }

    public function dokter()
    {
        return $this->hasOne(Dokter::class, 'rs1', 'rs9');
    }

    public function seprajal()
    {
        return $this->hasOne(Seprajal::class, 'rs1', 'rs1');
    }

    public function generalconsent()
    {
        return $this->hasOne(Mgeneralconsent::class, 'noreg', 'rs1');
    }

    public function taskid()
    {
        return $this->hasMany(Taskidantrian::class, 'noreg', 'rs1');
    }

    public function anamnesis()
    {
        return $this->hasMany(Anamnesis::class, 'rs1', 'rs1');
    }
    public function anamnesisAwalHd()
    {
        return $this->hasMany(Anamnesis::class, 'rs2', 'norm');
    }
    public function pemeriksaanfisik()
    {
        return $this->hasMany(Pemeriksaanfisik::class, 'rs1', 'rs1');
    }
    public function gambars()
    {
        return $this->hasMany(Simpangambarpemeriksaanfisik::class, 'noreg', 'rs1');
    }
    public function diagnosa()
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
    public function tindakan()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }
    public function laborats()
    {
        return $this->hasMany(LaboratMeta::class, 'noreg', 'rs1');
    }
    public function radiologi()
    {
        return $this->hasMany(Transpermintaanradiologi::class, 'rs1', 'rs1');
    }
    public function penunjanglain()
    {
        return $this->hasMany(Lain::class, 'rs1', 'rs1');
    }
    public function ok()
    {
        return $this->hasMany(PermintaanOperasi::class, 'rs1', 'rs1');
    }

    public function hasilradiologi()
    {
        return $this->hasMany(PembacaanradiologiController::class, 'rs1', 'rs1');
    }

    public function planning()
    {
        return $this->hasMany(WaktupulangPoli::class, 'rs1', 'rs1');
    }

    public function edukasi()
    {
        return $this->hasMany(Transedukasi::class, 'rs1', 'rs1');
    }

    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'rs9');
    }

    public function laborat()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function transradiologi()
    {
        return $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    public function apotekrajalpolilalu()
    {
        return $this->hasMany(Apotekrajallalu::class, 'rs1', 'rs1');
    }

    public function apotekrajal()
    {
        return $this->hasMany(Apotekrajal::class, 'rs1', 'rs1');
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

    public function kamaroperasi()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1', 'rs1');
    }

    public function tindakanoperasi()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function antrian_ambil()
    {
        return $this->hasMany(Antrianambil::class, 'noreg', 'rs1');
    }

    public function usg()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function ecg()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function eeg()
    {
        return $this->hasOne(Eegtrans::class, 'rs1', 'rs1');
    }

    public function pembacaanradiologi()
    {
        return $this->hasMany(PembacaanradiologiController::class, 'rs1', 'rs1');
    }

    public function fisio()
    {
        return $this->hasMany(Fisioterapipermintaan::class, 'rs1', 'rs1');
    }

    public function diet()
    {
        return $this->hasMany(DietTrans::class, 'noreg', 'rs1');
    }
    public function sharing()
    {
        return $this->hasMany(SharingTrans::class, 'noreg', 'rs1');
    }
    public function prosedur()
    {
        return $this->hasMany(ProcedureM::class, 'noreg', 'rs1');
    }

    public function newapotekrajal()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }
    public function newapotekrajalretur()
    {
        return $this->hasMany(Returpenjualan_h::class, 'noreg', 'rs1');
    }

    public function apotek()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }
    public function prb()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }

    public function satset()
    {
        return $this->hasOne(Satset::class, 'uuid', 'rs1');
    }
    public function satset_error()
    {
        return $this->hasOne(SatsetErrorRespon::class, 'uuid', 'rs1');
    }
    public function generalcons()
    {
        return $this->hasOne(Generalconsent::class, 'norm', 'norm');
    }

    //pegawai dari simpeg
    public function pegawai()
    {
        return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'rs9');
    }

    // masuk tanggal 22 maret 2024
    public function laporantindakan()
    {
        return $this->hasMany(LaporanTindakan::class, 'noreg', 'rs1');
    }
    public function psikiatri()
    {
        return $this->hasMany(PsikiatriPoli::class, 'noreg', 'rs1');
    }
    public function pemeriksaanfisikmata()
    {
        return $this->hasOne(Polimata::class, 'rs1', 'rs1');
    }

    public function jampulangtaskid()
    {
        return $this->hasMany(Bpjsrespontime::class, 'noreg', 'rs1');
    }

    public function neonatusmedis()
    {
        return $this->hasMany(NeonatusMedis::class, 'norm', 'rs2');
    }
    public function neonatuskeperawatan()
    {
        return $this->hasMany(NeonatusKeperawatan::class, 'norm', 'rs2');
    }
    public function pediatri()
    {
        return $this->hasMany(Pediatri::class, 'noreg', 'rs1');
    }
    public function kandungan()
    {
        return $this->hasMany(Kandungan::class, 'noreg', 'rs1');
    }
    public function dokumenluar()
    {
        return $this->hasMany(DokumenUpload::class, 'noreg', 'rs1');
    }
    public function permintaanperawatanjenazah()
    {
        return $this->hasMany(KamarjenazahPermintaan::class, 'rs1', 'rs1');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'rs2', 'rs1');
    }

    public function adminpoli()
    {
        return $this->belongsTo(Pembayaran::class, 'rs1', 'rs1');
    }

    public function konsulantarpoli()
    {
        return $this->belongsTo(Pembayaran::class, 'rs1', 'rs1');
    }

    public function tindakandokter()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }

    public function tindakanperawat()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }
    public function spri()
    {
        return $this->hasOne(Rs141::class, 'rs1', 'rs1');
    }
    public function tunggu_ranap()
    {
        return $this->hasOne(Kunjunganranap::class, 'rs1', 'flag');
    }
    public function doktersimpeg()
    {
        return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'rs9');
    }
    public function penilaiananamnesis()
    {
        return $this->hasMany(Penilaian::class, 'rs1', 'rs1');
    }
    public function historyperkawinan()
    {
        return $this->hasMany(HistoryPerkawinan::class, 'norm', 'rs2');
    }
    public function historykehamilan()
    {
        return $this->hasMany(HistoryKehamilan::class, 'noreg', 'rs1');
    }

    public function anamnesekebidanan()
    {
        return $this->hasMany(AnamnesisKebidanan::class, 'noreg', 'rs1');
    }

    public function ambulan()
    {
        return $this->hasMany(ReqAmbulan::class, 'rs1', 'rs1');
    }

    public function ambulantrans()
    {
        return $this->hasMany(Ambulan::class, 'rs1', 'rs1');
    }

    public function bankdarah()
    {
        return $this->hasMany(PermintaanBankdarah::class, 'rs1', 'rs1');
    }

    public function planheder()
    {
        return $this->hasMany(Planing_Igd_Lama::class, 'rs1', 'rs1');
    }

    public function laboratold()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function pemeriksaanfisikpsikologidll()
    {
        return $this->hasMany(PemeriksaanUmum::class, 'rs1', 'rs1');
    }

    public function konsuldokterspesialis()
    {
        return $this->hasMany(Konsultasi::class, 'noreg', 'rs1');
    }

    public function tinjauanulang()
    {
        return $this->hasMany(Tinjauan_ulang::class, 'noreg', 'rs1');
    }

    public function skalatransfer()
    {
        return $this->hasMany(SkalaTransferIgd::class, 'noreg', 'rs1');
    }

    public function telaahresep()
    {
        return $this->hasOne(TelaahResep::class, 'noreg', 'rs1');
    }

    public function peresepanobat()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }

    public function pemberianobat()
    {
        return $this->hasMany(PemberianObatIgd::class, 'noreg', 'rs1');
    }

    public function datacasmix()
    {
        return $this->hasOne(listcasmixrajal::class, 'noreg', 'rs1');
    }

    public function jawabankonsul()
    {
        return $this->hasMany(JawabanKonsulPoli::class,  'norm', 'rs2');
    }

    public function rencanaterapidokter()
    {
        return $this->hasMany(RencanaTerapiDokter::class,  'noreg', 'rs1');
    }

    // dipakai hd
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
    public function diagnosagizi()
    {
        return $this->hasMany(Diagnosagizi::class, 'noreg', 'rs1');
    }

    public function intradialitik()
    {
        return $this->hasMany(Intradialitik::class, 'rs1', 'noreg');
    }

    public function intradialitikhd()
    {
        return $this->hasMany(Intradialitik::class, 'rs1', 'rs1');
    }
    public function pengkajian()
    {
        return $this->hasMany(PengkajianHemodialisa::class, 'rs1', 'noreg');
    }
    public function konsultasi()
    {
        return $this->hasMany(Konsultasi::class, 'noreg', 'rs1');
    }

    public function kwitansilog()
    {
        return $this->hasMany(Kwitansilog::class, 'noreg', 'noreg');
    }
    public function karcislog()
    {
        return $this->hasMany(Karcis::class, 'noreg', 'noreg');
    }

    public function rs239_implementasi()
    {
        return $this->hasMany(ImplementasiEdukasi::class, 'noreg', 'rs1');
    }

    public function rs35x()
    {
        return $this->hasOne(Rstigalimax::class, 'rs1', 'rs1');
    }

    public function bankdarahtrans()
    {
        return $this->hasMany(Bdrstrans::class, 'rs1', 'rs1');
    }

    public function oktrans()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1', 'rs1');
    }

    public function kamarjenazah()
    {
        return $this->hasMany(Kamarjenasahtrans::class, 'rs1', 'rs1');
    }

    public function bpjssuratkontrol()
    {
        return $this->hasOne(Simpansuratkontrol::class, 'noreg', 'rs1');
    }

    public function jawabankonsulbynoreg()
    {
        return $this->hasMany(JawabanKonsulPoli::class,  'noreg_lama', 'rs1');
    }
}
