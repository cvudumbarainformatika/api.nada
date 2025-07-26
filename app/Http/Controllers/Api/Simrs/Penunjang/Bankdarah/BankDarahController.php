<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Bankdarah;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mbdrs;
use App\Models\Simrs\Penunjang\Bankdarah\PermintaanBankdarah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BankDarahController extends Controller
{

    public function getmaster()
    {

       $data = Cache::remember('m_bdrs', now()->addDays(7), function () {
          $master = Mbdrs::all();
          $master->makeHidden(['created_at','updated_at']);
          return $master;
        });


       return new JsonResponse($data);
    }

    public function getnota()
    {
        $nota = PermintaanBankdarah::select('rs2 as nota')->where('rs1', request('noreg'))
            ->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }
    // public function getdata()
    // {
    //     $data = PermintaanOperasi::select('*')->where('rs1', request('noreg'))
    //     ->with('petugas:kdpegsimrs,nik,nama,kdgroupnakes')
    //     ->orderBy('id', 'DESC')->get();
    //     return new JsonResponse($data);
    // }

    public function simpandata(Request $request)
    {

      $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

      if (count($cekKasir) > 0) {
        return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
      }

      DB::select('call nota_permintaanbankdarah(@nomor)');
      $x = DB::table('rs1')->select('rs54')->get();
      $wew = $x[0]->rs54;

      $nota = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'B');

      $userid = FormatingHelper::session_user();
      $simpan = PermintaanBankdarah::firstOrCreate(
          [
              'rs1' => $request->noreg,
              'rs2' => $nota,
          ],
          [
              'rs3' => date('Y-m-d H:i:s'),
              'rs4' => $request->jenis,
              'rs5' => $request->gol,
              'rs6' => $request->jumlah,
              'rs7' => $request->sifatpermintaan,
              // 'rs8' => $request->kodedokter, //$request->kodedokter
              'rs9' => $request->reaksi,
              'rs10' => $request->kodedokter,
              'rs11' => $request->kdgroup_ruangan,
              'rs12' => $request->kodesistembayar,
              'rs13' => $request->transfusike,
              'rs14' => '',
              'rs15' => $request->rhesus,
              'rs16' => $request->kodepoli,
              'rs17' => $request->kodeperawat,
              'rs18' => $request->perawatpeminta,
              'ket' => $request->keterangan ?? null,
          ]
      );

      if (!$simpan) {
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
      }
      $nota = PermintaanBankdarah::select('rs2 as nota')->where('rs1', $request->noreg)
          ->orderBy('id', 'DESC')->get();

      return new JsonResponse(
          [
              'message' => 'Permintaan Bank Darah Berhasil di Simpan',
              'result' => $simpan,
              'nota' => $nota
          ],
          200
      );
    }

    public function hapusdata(Request $request)
    {
        $cari = PermintaanBankdarah::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->rs14 === '1'; // ini masih tanda tanya
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = PermintaanBankdarah::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }

    public function simpanPermintaanDarahIgd(Request $request)
    {

        DB::select('call nota_permintaanbankdarah(@nomor)');
        $x = DB::table('rs1')->select('rs54')->get();
        $wew = $x[0]->rs54;

        $nota = FormatingHelper::formatallpermintaan($wew, 'B');

        $userid = FormatingHelper::session_user();
        $simpan = PermintaanBankdarah::firstOrCreate(
            [
                'rs1' => $request->noreg,
                'rs2' => $nota,
            ],
            [
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->jenisdarah,
                'rs5' => $request->golda,
                'rs6' => $request->jumlahbag,
                'rs7' => $request->sifatpermintaan,
                // 'rs8' => $request->kodedokter, //$request->kodedokter
                'rs9' => $request->reaksi,
                'rs10' => $request->kodedokter,
                'rs11' => $request->koderuang,
                'rs12' => $request->kodesistembayar,
                'rs13' => $request->transfusike,
                'rs14' => '',
                'rs15' => $request->rhesus,
                'rs16' => $request->koderuang,
                'rs17' => $userid['kodesimrs'],
                'rs18' => $userid['kodesimrs'],
                'rs19' => $request->sebutkan,
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        $nota = PermintaanBankdarah::select('rs2 as nota')->where('rs1', $request->noreg)
            ->orderBy('id', 'DESC')->groupBy('rs2')->get();

        return new JsonResponse(
            [
                'message' => 'Permintaan Bank Darah Berhasil di Simpan',
                'data' => $simpan,
                'nota' => $nota
            ],
            200
        );
    }

    public function hapusdataIgd(Request $request)
    {
        $cari = PermintaanBankdarah::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->rs14 === '1'; // ini masih tanda tanya
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = PermintaanBankdarah::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
