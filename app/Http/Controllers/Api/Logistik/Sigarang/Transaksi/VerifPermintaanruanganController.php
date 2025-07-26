<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MapingBarangDepo;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifPermintaanruanganController extends Controller
{
    // ambil semua permintaan yang sudah selesai di input
    public function getPermintaan()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $datas = Permintaanruangan::where('status', '=', 4)
            ->with([
                // 'details.barangrs', 'details.satuan', 'details.ruang',
                'pj', 'pengguna', 'details' => function ($wew) use ($pegawai) {
                    if ($pegawai->role_id === 4) {
                        $wew->where('dari', $pegawai->kode_ruang);
                    }
                    $wew->with('barangrs', 'satuan', 'ruang');
                }
            ])->get();
        // if (count($data)) {
        //     foreach ($data as $key) {
        //         $key->gudang = collect($key->details)->groupBy('dari');
        //     }
        // }
        // dari itu === kode_depo
        // tujuan itu === kode_ruangn
        foreach ($datas as $key) {
            foreach ($key->details as $detail) {
                $temp = $this->stokRuanganByBarang($detail->kode_rs, $detail->tujuan, $detail->dari, $detail->id);
                $detail->alokasi = $temp->alokasi;
                $detail->stokDepo = $temp->stokDepo;
                $detail->stokRuangan = $temp->stokRuangan;
            }
            $key->user = $pegawai;
        }

        return new JsonResponse($datas);
    }
    public function stokRuanganByBarang($kode_rs, $kode_ruangan, $kode_depo, $detailId)
    {
        // $kode_rs = request('kode_rs');
        // $kode_ruangan = request('kode_ruangan');

        // ambil data barang tidak puerlu karena sudah ada
        // $barang = BarangRS::where('kode', $kode_rs)->first();

        // cari barang ini masuk depo mana
        // $depo = MapingBarangDepo::where('kode_rs', $kode_rs)->first();

        // ambil stok ruangan
        $stokRuangan = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_ruangan)->get();

        $totalStokRuangan = collect($stokRuangan)->sum('sisa_stok');
        // $totalStokRuangan = RecentStokUpdate::where('kode_rs', $kode_rs)
        //     ->where('kode_ruang', $kode_ruangan)->sum('sisa_stok');

        // $totalStokRuangan = collect($stokRuangan)->sum('sisa_stok');

        // cari stok di depo
        $stok = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_depo)->get();

        $totalStok = collect($stok)->sum('sisa_stok');
        // $totalStok = RecentStokUpdate::where('kode_rs', $kode_rs)
        //     ->where('kode_ruang', $kode_depo)->sum('sisa_stok');

        // $totalStok = collect($stok)->sum('sisa_stok');

        // ambil alokasi barang
        $data = DetailPermintaanruangan::whereHas('permintaanruangan', function ($q) {
            $q->where('status', '>=', 4)
                ->where('status', '<', 7);
        })->where('kode_rs', $kode_rs)->get();
        // $data = DB::connection('sigarang')->table('detail_permintaanruangans')
        //     ->select(
        //         'detail_permintaanruangans.permintaanruangan_id',
        //         'detail_permintaanruangans.kode_rs',
        //         'detail_permintaanruangans.jumlah_disetujui',
        //         'detail_permintaanruangans.jumlah',
        //         'permintaanruangan.id',
        //         'permintaanruangan.status',
        //     )
        //     ->join('permintaanruangans', function ($minta) {
        //         $minta->on(
        //             'detail_permintaanruangans.permintaanruangan_id',
        //             '=',
        //             'permintaanruangans.id'
        //         )
        //             ->whereIn('status', [4, 5, 6]);
        //     })->where('kode_rs', $kode_rs)->get();

        $col = collect($data);

        $gr = $col->map(function ($item) {
            $jumsem = $item->jumlah_disetujui ? $item->jumlah_disetujui : $item->jumlah;
            $item->alokasi = $jumsem;
            return $item;
        });

        $sum = $gr->sum('alokasi');

        $alokasi = 0;
        // ambil permintaan dari ruangan ybs
        // $permintaanRuangan = DetailPermintaanruangan::whereHas('permintaanruangan', function ($q) {
        //     $q->where('status', '>=', 4)
        //         ->where('status', '<', 7);
        // })->where('kode_rs', $kode_rs)->where('tujuan', $kode_ruangan)->first();
        $permintaanRuangan = DetailPermintaanruangan::find($detailId);
        // jumlah alokasi depo dikurangi permintaan ruangan
        $myAlokasi = $sum - $permintaanRuangan->jumlah;
        // hitung alokasi
        if ($totalStok >= $myAlokasi) {
            $alokasi =  $totalStok - $myAlokasi;
        } else {
            $alokasi = 0;
        }
        $barang = (object) [];
        $barang->alokasi = $alokasi;
        $barang->stokDepo = $totalStok;
        $barang->stokRuangan = $totalStokRuangan;
        // $barang->kode_rs = $kode_rs;
        // $barang->kode_ruangan = $kode_ruangan;
        // $barang->kode_depo = $kode_depo;
        return $barang;
    }

    public function updatePermintaan(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'details' => 'required',
        ]);
        $details = $request->details;
        $permintaan = Permintaanruangan::updateOrCreate(['id' => $request->id], $request->only('status', 'tanggal_verif'));

        foreach ($details as $value) {
            $id = $value['id'];
            $permintaan->details()->updateOrCreate(['id' => $id], $value);
        }
        if (!$permintaan->wasChanged()) {
            return new JsonResponse(['message' => 'data gagal di update'], 501);
        }
        return new JsonResponse(['data' => $permintaan, 'message' => 'data berhasil di simpan'], 200);
    }

    public function tolakPermintaan(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);
        // $details = $request->details;
        if ($request->status === 6 || $request->status === '6') {

            $permintaanruangan = Permintaanruangan::with('details')->find($request->id);

            foreach ($permintaanruangan->details as $key => $detail) {
                // if (!$detail['jumlah_disetujui']) {
                //     return new JsonResponse(['message' => 'periksa kembali jumlah disetujui'], 422);
                // }
                if (!$detail['tujuan']) {
                    return new JsonResponse(['message' => 'periksa data ruangan yang melakukan permintaan'], 422);
                }
                $stok = RecentStokUpdate::selectRaw('kode_rs,kode_ruang, sisa_stok, sum(sisa_stok) as stok')
                    ->where('kode_rs', $detail['kode_rs'])
                    ->where('kode_ruang', $detail['dari'])
                    ->with('barang')
                    ->groupBy('kode_rs')
                    ->first();

                // return new JsonResponse(['stok' => $stok, 'detail' => $detail]);
                if (!$stok) {
                    $barang = BarangRS::where('kode', $detail['kode_rs'])->first();
                    return new JsonResponse(['stok' => $stok, 'detail' => $detail, 'message' => 'Stok ' . $barang->nama . ' tidak ada'], 410);
                }
                if (($detail['jumlah_distribusi'] > 0) && ($detail['jumlah_distribusi'] > $stok->stok)) {
                    return new JsonResponse(['stok' => $stok, 'detail' => $detail, 'message' => 'jumlah Stok tidak mencukupi'], 410);
                }
            }
        }
        $permintaan = Permintaanruangan::updateOrCreate(['id' => $request->id], $request->only('status'));

        // foreach ($details as $value) {
        //     $id = $value['id'];
        //     $permintaan->details()->updateOrCreate(['id' => $id], $value);
        // }
        if (!$permintaan->wasChanged()) {
            return new JsonResponse(['message' => 'data gagal di update'], 501);
        }
        $pesan = 'Status Permintaan sudah berganti';

        return new JsonResponse(['data' => $permintaan, 'message' => $pesan], 200);
    }
}
