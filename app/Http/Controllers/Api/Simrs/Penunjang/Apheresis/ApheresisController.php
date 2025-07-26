<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Apheresis;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mbdrs;
use App\Models\Simrs\Master\Mtindakanapheresis;
use App\Models\Simrs\Penunjang\Apheresis\PermintaanApheresis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApheresisController extends Controller
{

    public function getmaster()
    {

       $data = Cache::remember('m_tindakan_apheresis', now()->addDays(7), function () {
          return Mtindakanapheresis::all();
        });

      // $data = Mtindakanapheresis::all();
      return new JsonResponse($data);
    }

    public function getnota()
    {
        $nota = PermintaanApheresis::select('nota_permintaan as nota')->where('noreg', request('noreg'))
            ->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }
    public function getdata()
    {
        $data = PermintaanApheresis::select('*')->where('noreg', request('noreg'))
        // ->with('petugas:kdpegsimrs,nik,nama,kdgroupnakes')
        ->orderBy('id', 'DESC')->get();
        return new JsonResponse($data);
    }

    public function simpandata(Request $request)
    {

      $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

      if (count($cekKasir) > 0) {
        return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
      }

      DB::select('call nota_permintaan_apheresis(@nomor)');
      $x = DB::table('rs1')->select('permintaan_apheresis')->get();
      $wew = $x[0]->permintaan_apheresis;

      $nota = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'PA');

      $userid = FormatingHelper::session_user();
      $simpan = PermintaanApheresis::firstOrCreate(
          [
              'noreg' => $request->noreg,
              'nota_permintaan' => $nota,
          ],
          [
            'norm'=> $request->norm,
            'tgl_permintaan' => date('Y-m-d H:i:s'),
            'tindakan' => $request->tindakan,
            'js' => $request->js,
            'jp' => $request->jp,
            'keterangan' => $request->keterangan,
            'ruangan' => $request->kodepoli,
            'user_entry'=> $userid['kodesimrs'],
            'sistembayar' => $request->kodesistembayar,
            'ruangan_induk' => $request->kdgroup_ruangan,
            'golongan' => $request->gol,
            'jumlah' => $request->jumlah,
            'transfusi' => $request->transfusike,
            'reaksi' => $request->reaksi,
            'lainnya'=> '',
            'kodeperawat' => $request->kodeperawat,
            'perawatyanmeminta' => $request->perawatyanmeminta,
            'kodedokterdpjp'=> $request->kodedokter,
            'namadokterdpjp'=> $request->namadokterdpjp,
          ]
      );

      if (!$simpan) {
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
      }
      $nota = PermintaanApheresis::select('nota_permintaan as nota')->where('noreg', $request->noreg)
          ->orderBy('id', 'DESC')->get();

      return new JsonResponse(
          [
              'message' => 'Permintaan Apheresis Berhasil di Simpan',
              'result' => $simpan,
              'nota' => $nota
          ],
          200
      );
    }

    public function hapusdata(Request $request)
    {

        $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

        if (count($cekKasir) > 0) {
            return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
        }

        $cari = PermintaanApheresis::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->kunci === '1' || $cari->kunci === 1; // ini masih tanda tanya
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci !!!'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = PermintaanApheresis::select('nota_permintaan as nota')->where('noreg', $request->noreg)
          ->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
