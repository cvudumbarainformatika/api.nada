<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\StokOpname;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KartustokController extends Controller
{
    public function kartustokgudang()
    {
        $kd_tempat = 'Gd-02010100';
        $kd_barang = request('kd_barang');
        $bln    = request('bln');
        $thn    = request('thn');

        if ($bln == 1) {
            $blnx = 12;
            $thnx = $thn - 1;
        } else {
            $blnx = $bln - 1;
            $thnx = $thn;
        }

        $query = BarangRS::select('kode as kode', 'nama as nama')->with(
            [
                'stok_awal' => function ($stokawal) use ($bln, $blnx, $thn, $thnx, $kd_tempat) {
                    $stokawal->select(
                        'monthly_stok_updates.id',
                        'monthly_stok_updates.tanggal',
                        'monthly_stok_updates.no_penerimaan',
                        'monthly_stok_updates.harga',
                        'monthly_stok_updates.sisa_stok',
                        'monthly_stok_updates.kode_satuan',
                        'monthly_stok_updates.kode_rs'
                    )
                        ->whereMonth('monthly_stok_updates.tanggal', $blnx)
                        ->whereYear('monthly_stok_updates.tanggal', $thnx)
                        ->where('monthly_stok_updates.kode_ruang', '=', $kd_tempat)
                        ->with('satuan:nama,kode');
                },
                'masukgudang' => function ($penerimaan) use ($bln, $blnx, $thn, $thnx, $kd_tempat) {
                    $penerimaan->select(
                        'penerimaans.id as id',
                        'penerimaans.tanggal as tanggal',
                        'penerimaans.no_penerimaan',
                        'detail_penerimaans.qty as masuk',
                        'detail_penerimaans.harga as harga',
                        'detail_penerimaans.satuan_besar as satuan_besar',
                        'penerimaans.nomor as nomor'
                    )
                        ->whereMonth('penerimaans.tanggal', $bln)
                        ->whereYear('penerimaans.tanggal', $thn);
                },
                'masukgudang.pemesanan:id,nomor,kode_perusahaan', 'masukgudang.pemesanan.perusahaan:kode,nama',
                'keluargudang' => function ($keluar) use ($bln, $blnx, $thn, $thnx, $kd_tempat) {
                    $keluar->select(
                        'distribusi_depos.id as id',
                        'distribusi_depos.tanggal as tanggal',
                        'distribusi_depos.no_distribusi',
                        'detail_distribusi_depos.jumlah as keluar',
                        'detail_distribusi_depos.no_penerimaan',
                        'detail_distribusi_depos.satuan_besar',
                        'distribusi_depos.kode_depo as kode_depo'
                    )
                        ->where('distribusi_depos.status', '=', '2')
                        ->whereMonth('distribusi_depos.tanggal', $bln)
                        ->whereYear('distribusi_depos.tanggal', $thn)->with(['depo:kode,nama']);
                },
                'stok_akhir' => function ($stokahir) use ($bln, $blnx, $thn, $thnx, $kd_tempat) {
                    $stokahir->select(
                        'monthly_stok_updates.id',
                        'monthly_stok_updates.tanggal',
                        'monthly_stok_updates.no_penerimaan',
                        'monthly_stok_updates.harga',
                        'monthly_stok_updates.sisa_stok',
                        'monthly_stok_updates.kode_satuan',
                        'monthly_stok_updates.kode_rs'
                    )
                        ->whereMonth('monthly_stok_updates.tanggal', $bln)
                        ->whereYear('monthly_stok_updates.tanggal', $thn)
                        ->where('monthly_stok_updates.kode_ruang', '=', $kd_tempat)
                        ->with('satuan:nama,kode');
                }
            ]
        )
            ->where('kode', '=', $kd_barang)
            ->get();
        // ->first();

        return new JsonResponse($query);
    }

    public function kartustokdepo()
    {
        $kd_depo = request('kd_tempat');
        $kd_barang = request('kd_barang');
        $bln    = request('bln');
        $thn    = request('thn');

        if ($bln == 1) {
            $blnx = 12;
            $thnx = $thn - 1;
        } else {
            $blnx = $bln - 1;
            $thnx = $thn;
        }

        $query = BarangRS::select('kode as kode', 'nama as nama')->with(
            [
                'stok_awal' => function ($stokawal) use ($bln, $blnx, $thn, $thnx, $kd_depo) {
                    $stokawal->select(
                        'monthly_stok_updates.id',
                        'monthly_stok_updates.tanggal',
                        'monthly_stok_updates.no_penerimaan',
                        'monthly_stok_updates.harga',
                        'monthly_stok_updates.sisa_stok',
                        'monthly_stok_updates.kode_satuan',
                        'monthly_stok_updates.kode_rs'
                    )
                        ->whereMonth('monthly_stok_updates.tanggal', $blnx)
                        ->whereYear('monthly_stok_updates.tanggal', $thnx)
                        ->where('monthly_stok_updates.kode_ruang', '=', $kd_depo)
                        ->with('satuan:nama,kode');
                },
                'keluargudang' => function ($masukdepo) use ($bln, $blnx, $thn, $thnx, $kd_depo) {
                    $masukdepo->select(
                        'distribusi_depos.id as id',
                        'distribusi_depos.tanggal as tanggal',
                        'distribusi_depos.no_distribusi',
                        'detail_distribusi_depos.jumlah as keluar',
                        'detail_distribusi_depos.no_penerimaan',
                        'detail_distribusi_depos.satuan_besar',
                        'distribusi_depos.kode_depo as kode_depo'
                    )
                        ->where('distribusi_depos.status', '=', '2')
                        ->whereMonth('distribusi_depos.tanggal', $bln)
                        ->whereYear('distribusi_depos.tanggal', $thn)->with(['depo:kode,nama']);
                },
                'pengeluarandepo' => function ($pengeluarandepo) use ($bln, $blnx, $thn, $thnx, $kd_depo, $kd_barang) {
                    $pengeluarandepo->select(
                        'detail_permintaanruangans.kode_satuan',
                        'permintaanruangans.id',
                        'permintaanruangans.no_distribusi',
                        'permintaanruangans.tanggal_distribusi as tanggal',
                        'permintaanruangans.kode_pengguna',
                        'permintaanruangans.kode_ruang',
                        'detail_permintaanruangans.jumlah_distribusi as jumlah',
                        'detail_permintaanruangans.no_penerimaan'
                    )
                        ->where('permintaanruangans.status', '=', '7')
                        ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
                        ->whereMonth('permintaanruangans.tanggal_distribusi', $bln)
                        ->whereYear('permintaanruangans.tanggal_distribusi', $thn)
                        ->with([
                            'masterdepo:kode,nama',
                            'ruangan:kode,uraian'
                        ]);
                },
                'stok_akhir' => function ($stokahir) use ($bln, $blnx, $thn, $thnx, $kd_depo) {
                    $stokahir->select(
                        'monthly_stok_updates.id',
                        'monthly_stok_updates.tanggal',
                        'monthly_stok_updates.no_penerimaan',
                        'monthly_stok_updates.harga',
                        'monthly_stok_updates.sisa_stok',
                        'monthly_stok_updates.kode_satuan',
                        'monthly_stok_updates.kode_rs'
                    )
                        ->whereMonth('monthly_stok_updates.tanggal', $bln)
                        ->whereYear('monthly_stok_updates.tanggal', $thn)
                        ->where('monthly_stok_updates.kode_ruang', '=', $kd_depo)
                        ->with('satuan:nama,kode');
                }
            ]
        )
            ->where('kode', '=', $kd_barang)
            ->get();
        // ->first();

        return new JsonResponse($query);
    }

    public function kartustokruangan()
    {
        $kd_ruangan = request('kd_tempat');
        $kd_barang = request('kd_barang');
        $bln    = request('bln');
        $thn    = request('thn');

        if ($bln == 1) {
            $blnx = 12;
            $thnx = $thn - 1;
        } else {
            $blnx = $bln - 1;
            $thnx = $thn;
        }

        $query = BarangRS::select('kode as kode', 'nama as nama', 'kode_satuan')->with(
            [
                'stok_awal' => function ($stokawal) use ($bln, $blnx, $thn, $thnx, $kd_ruangan) {
                    $stokawal->select(
                        'monthly_stok_updates.id',
                        'monthly_stok_updates.tanggal',
                        'monthly_stok_updates.no_penerimaan',
                        'monthly_stok_updates.harga',
                        'monthly_stok_updates.sisa_stok',
                        'monthly_stok_updates.kode_satuan',
                        'monthly_stok_updates.kode_rs'
                    )
                        ->whereMonth('monthly_stok_updates.tanggal', $blnx)
                        ->whereYear('monthly_stok_updates.tanggal', $thnx)
                        ->where('monthly_stok_updates.kode_ruang', '=', $kd_ruangan)
                        ->with('satuan:nama,kode');
                },
                'pengeluarandepo' => function ($pengeluarandepo) use ($bln, $blnx, $thn, $thnx, $kd_ruangan) {
                    $pengeluarandepo->select(
                        'permintaanruangans.id',
                        'permintaanruangans.no_distribusi',
                        'permintaanruangans.tanggal',
                        'permintaanruangans.kode_pengguna',
                        'detail_permintaanruangans.jumlah_distribusi as jumlah',
                        'detail_permintaanruangans.no_penerimaan',
                        'permintaanruangans.dari'
                    )
                        ->where('permintaanruangans.status', '=', '7')
                        ->where('permintaanruangans.kode_ruang', $kd_ruangan)
                        ->whereMonth('permintaanruangans.tanggal', $bln)
                        ->whereYear('permintaanruangans.tanggal', $thn)->with(['masterdepo:kode,nama']);
                },
                'pemakaianruangan' => function ($pemakaianruangan) use ($bln, $blnx, $thn, $thnx, $kd_ruangan) {
                    $pemakaianruangan->select(
                        'pemakaianruangans.id',
                        'pemakaianruangans.reff',
                        'pemakaianruangans.tanggal',
                        'pemakaianruangans.kode_ruang',
                        'details_pemakaianruangans.jumlah',
                        'details_pemakaianruangans.no_penerimaan'
                    )
                        ->where('pemakaianruangans.kode_ruang', $kd_ruangan)
                        ->whereMonth('pemakaianruangans.tanggal', $bln)
                        ->whereYear('pemakaianruangans.tanggal', $thn)->with(['ruanganmaster:kode,uraian']);
                },
                'stok_akhir' => function ($stokahir) use ($bln, $blnx, $thn, $thnx, $kd_ruangan) {
                    $stokahir->select(
                        'monthly_stok_updates.id',
                        'monthly_stok_updates.tanggal',
                        'monthly_stok_updates.no_penerimaan',
                        'monthly_stok_updates.harga',
                        'monthly_stok_updates.sisa_stok',
                        'monthly_stok_updates.kode_satuan',
                        'monthly_stok_updates.kode_rs'
                    )
                        ->whereMonth('monthly_stok_updates.tanggal', $bln)
                        ->whereYear('monthly_stok_updates.tanggal', $thn)
                        ->where('monthly_stok_updates.kode_ruang', '=', $kd_ruangan)
                        ->with('satuan:nama,kode');
                },
                'satuan:kode,nama'
            ]
        )
            ->where('kode', '=', $kd_barang)
            ->get();
        // ->first();

        return new JsonResponse($query);
    }
}
