<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Master\Akun_jurnal;
use App\Models\Siasik\Master\Akun_mapjurnal;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\RestriksiObat;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\RestriksiObatKecualiRuangan;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PengembalianRinciFifo;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\ReturGudangDetail;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PemakaianR;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\PenyesuaianStok;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokOpnameFisik;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokopnameSementara;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokrealSementara;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Mobatnew extends Model
{
    use HasFactory;
    //   use SoftDeletes;
    protected $table = 'new_masterobat';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';


    public function getHargaAttribute()
    {
        $kdobat = $this->kd_obat;
        $daftar = DaftarHarga::selectRaw('max(harga) as harga')
            ->where('kd_obat', $kdobat)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->limit(5)
            ->first();
        $harga = $daftar->harga;
        return $harga;
    }
    public function scopeMobat($data)
    {
        return $data->select([
            'kd_obat as kodeobat',
            'nama_obat as namaobat'
        ]);
    }

    public function kodebelanja()
    {
        return $this->belongsTo(Mkodebelanjaobat::class, 'kode108', 'kode');
    }
    public function scopeFilter($cari, array $reqs)
    {
        $cari->when(
            $reqs['q'] ?? false,
            function ($data, $query) {
                return $data->where('flag', '')
                    ->where('kd_obat', 'LIKE', '%' . $query . '%')
                    ->orWhere('nama_obat', 'LIKE', '%' . $query . '%')
                    ->orderBy('nama_obat');
            }
        );
    }


    public function daftarharga()
    {
        return $this->hasMany(DaftarHarga::class, 'kd_obat', 'kd_obat');
    }
    public function indikasi()
    {
        return $this->hasMany(IndikasiObat::class, 'kd_obat', 'kd_obat');
    }
    public function mkelasterapi()
    {
        return $this->hasMany(Mapingkelasterapi::class, 'kd_obat', 'kd_obat');
    }


    public function kfa()
    {
        return $this->hasOne(MapingKfa::class, 'kd_obat', 'kd_obat');
    }
    public function onestok()
    {
        return $this->hasOne(Stokreal::class, 'kdobat', 'kd_obat');
    }
    public function onefisik()
    {
        return $this->hasOne(StokOpnameFisik::class, 'kdobat', 'kd_obat');
    }
    public function fisik()
    {
        return $this->hasMany(StokOpnameFisik::class, 'kdobat', 'kd_obat');
    }

    public function stokrealgudang()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokrealallrs()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokmaxrs()
    {
        return $this->hasMany(Mminmaxobat::class, 'kd_obat', 'kd_obat');
    }

    public function perencanaanrinci()
    {
        return $this->hasMany(RencanabeliR::class, 'kdobat', 'kd_obat');
    }
    public function pemesananrinci()
    {
        return $this->hasMany(PemesananRinci::class, 'kdobat', 'kd_obat');
    }

    public function stokrealgudangko()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokrealgudangfs()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokmaxpergudang()
    {
        return $this->hasMany(Mminmaxobat::class, 'kd_obat', 'kd_obat');
    }
    public function stok()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }
    public function saldoawal()
    {
        return $this->hasMany(Stokopname::class, 'kdobat', 'kd_obat');
    }
    public function saldoakhir()
    {
        return $this->hasMany(Stokopname::class, 'kdobat', 'kd_obat');
    }
    // ini untuk test
    // public function saldoawal()
    // {
    //     return $this->hasMany(StokopnameSementara::class, 'kdobat', 'kd_obat');
    // }
    // public function saldoakhir()
    // {
    //     return $this->hasMany(StokopnameSementara::class, 'kdobat', 'kd_obat');
    // }
    // public function stok()
    // {
    //     return $this->hasMany(StokrealSementara::class, 'kdobat', 'kd_obat');
    // }

    // test end
    public function oneopname()
    {
        return $this->hasOne(Stokopname::class, 'kdobat', 'kd_obat');
    }

    public function penerimaanrinci()
    {
        return $this->hasMany(PenerimaanRinci::class, 'kdobat', 'kd_obat');
    }
    public function hargapenerimaanrinci()
    {
        return $this->hasMany(PenerimaanRinci::class, 'kdobat', 'kd_obat');
    }
    public function mutasi()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat');
    }
    public function mutasimasuk()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat');
    }
    public function mutasikeluar()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat');
    }


    // mutasi ngambang untuk laporan mutasi fifo start ------
    public function mutasimasukngambang()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat')
            ->select(
                'mutasi_gudangdepo.no_permintaan',
                'mutasi_gudangdepo.kd_obat',
                'mutasi_gudangdepo.kd_obat as kdobat',
                'mutasi_gudangdepo.nopenerimaan',
                'mutasi_gudangdepo.harga',
                DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as sub'),
                'permintaan_h.tgl_kirim_depo',
                'permintaan_h.tgl_terima_depo',
                'permintaan_h.tgl_terima_depo as tgl',
                'permintaan_h.dari as kdruang'
            )
            ->join('permintaan_h', 'mutasi_gudangdepo.no_permintaan', '=', 'permintaan_h.no_permintaan')
            ->whereRaw("DATE_FORMAT(permintaan_h.tgl_kirim_depo, '%Y-%m') != DATE_FORMAT(permintaan_h.tgl_terima_depo, '%Y-%m')")
            ->where('permintaan_h.dari', 'LIKE', 'Gd-%');
    }
    public function mutasikeluarngambang()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'kd_obat', 'kd_obat')
            ->select(
                'mutasi_gudangdepo.no_permintaan',
                'mutasi_gudangdepo.kd_obat',
                'mutasi_gudangdepo.kd_obat as kdobat',
                'mutasi_gudangdepo.nopenerimaan',
                'mutasi_gudangdepo.harga',
                DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as sub'),
                'permintaan_h.tgl_kirim_depo',
                'permintaan_h.tgl_terima_depo',
                'permintaan_h.tgl_kirim_depo as tgl',
                'permintaan_h.dari as kdruang'
            )
            ->join('permintaan_h', 'mutasi_gudangdepo.no_permintaan', '=', 'permintaan_h.no_permintaan')
            ->whereRaw("DATE_FORMAT(permintaan_h.tgl_kirim_depo, '%Y-%m') != DATE_FORMAT(permintaan_h.tgl_terima_depo, '%Y-%m')")
            ->where('permintaan_h.dari', 'LIKE', 'Gd-%');
    }
    // mutasi ngambang untuk laporan mutasi fifo start ------


    public function persiapanoperasirinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function persiapanoperasiretur()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function persiapanoperasikeluar()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function resepkeluar()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kd_obat');
    }
    public function resepkeluarok()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kd_obat');
    }
    public function oneobatkel()
    {
        return $this->hasOne(Resepkeluarrinci::class, 'kdobat', 'kd_obat');
    }
    public function pemakaian()
    {
        return $this->hasMany(PemakaianR::class, 'kd_obat', 'kd_obat');
    }
    public function resepkeluarracikan()
    {
        return $this->hasMany(Resepkeluarrinciracikan::class, 'kdobat', 'kd_obat');
    }
    public function oneobatkelracikan()
    {
        return $this->hasOne(Resepkeluarrinciracikan::class, 'kdobat', 'kd_obat');
    }

    public function permintaandeporinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'kdobat', 'kd_obat');
    }
    public function onepermintaandeporinci()
    {
        return $this->hasOne(Permintaandeporinci::class, 'kdobat', 'kd_obat');
    }

    public function transnonracikan()
    {
        // return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kdobat'); //diganti ke permintaan
        return $this->hasMany(Permintaanresep::class, 'kdobat', 'kd_obat');
    }
    public function onepermintaan()
    {
        // return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kdobat'); //diganti ke permintaan
        return $this->hasOne(Permintaanresep::class, 'kdobat', 'kd_obat');
    }

    public function transracikan()
    {
        // return $this->hasMany(Resepkeluarrinciracikan::class, 'kdobat', 'kdobat');
        return $this->hasMany(Permintaanresepracikan::class, 'kdobat', 'kd_obat');
    }
    public function oneperracikan()
    {
        // return $this->hasMany(Resepkeluarrinciracikan::class, 'kdobat', 'kdobat');
        return $this->hasOne(Permintaanresepracikan::class, 'kdobat', 'kd_obat');
    }

    public function persiapanrinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }
    public function distribusipersiapan()
    {
        return $this->hasMany(PersiapanOperasiDistribusi::class, 'kd_obat', 'kd_obat');
    }
    public function persiapanretur()
    {
        return $this->hasMany(PersiapanOperasiDistribusi::class, 'kd_obat', 'kd_obat');
    }
    public function returpenjualan()
    {
        return $this->hasMany(Returpenjualan_r::class, 'kdobat', 'kd_obat');
    }
    public function permintaanobatrinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'kdobat', 'kd_obat');
    }
    public function barangrusak()
    {
        return $this->hasMany(BarangRusak::class, 'kd_obat', 'kd_obat');
    }

    public function pagu()
    {
        return $this->belongsTo(PergeseranPaguRinci::class, 'kode108', 'koderek108');
    }
    public function realisasi()
    {
        return $this->hasMany(NpdLS_rinci::class, 'koderek108', 'kode108');
    }
    public function returgudang()
    {
        return $this->hasMany(ReturGudangDetail::class, 'kd_obat', 'kd_obat');
    }
    public function returdepo()
    {
        return $this->hasMany(ReturGudangDetail::class, 'kd_obat', 'kd_obat');
    }
    public function penyesuaian()
    {
        return $this->hasMany(PenyesuaianStok::class, 'kdobat', 'kd_obat');
    }
    public function jurnal()
    {
        return $this->hasOne(Akun_mapjurnal::class, 'kodeall', 'kode50');
    }
    public function returpbf()
    {
        return $this->hasMany(Returpbfrinci::class, 'kd_obat', 'kd_obat');
    }

    public function pengembalianrincififo()
    {
        return $this->hasMany(PengembalianRinciFifo::class, 'kdobat', 'kd_obat');
    }

    public function restriksiobat()
    {
        return $this->hasOne(RestriksiObat::class, 'kd_obat', 'kd_obat');
    }
    public function kecuali()
    {
        return $this->hasMany(RestriksiObatKecualiRuangan::class, 'kd_obat', 'kd_obat');
    }
}
