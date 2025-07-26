<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Cathlab;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mbdrs;
use App\Models\Simrs\Penunjang\Cathlab\ReqCathlab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermintaanCathlabController extends Controller
{

    // public function getmaster()
    // {

    //    $data = Cache::remember('m_tindakan_apheresis', now()->addDays(7), function () {
    //       return Mtindakanapheresis::all();
    //     });

    //   // $data = Mtindakanapheresis::all();
    //   return new JsonResponse($data);
    // }

    public function getnota()
    {
        $nota = ReqCathlab::select('nota as nota')->where('noreg', request('noreg'))
            ->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }
    public function getdata()
    {
        $data = ReqCathlab::select('*')->where('noreg', request('noreg'))
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

      DB::select('call nota_cathlab(@nomor)');
      $x = DB::table('rs1')->select('cathlab')->get();
      $wew = $x[0]->cathlab;

      $nota = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'CL');

      $userid = FormatingHelper::session_user();
      $simpan = ReqCathlab::firstOrCreate(
          [
              'noreg' => $request->noreg,
              'nota' => $nota,
          ],
          [
            'norm'=> $request->norm,
            'tgl' => date('Y-m-d H:i:s'),
            'keterangan' => $request->keterangan,
            'kd_ruangkelas' => $request->kodepoli,
            'kelas'=>  $request->kelas_ruangan,
            'userinput'=> $userid['kodesimrs'],
            'kdruang' => $request->kdgroup_ruangan,
            'sistembayar' => $request->kodesistembayar,
            'dokterpengirim' => $request->dokterpengirim
          ]
      );

      if (!$simpan) {
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
      }
      $nota = ReqCathlab::select('nota as nota')->where('noreg', $request->noreg)
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
        $cari = ReqCathlab::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->flag === '1' || $cari->flag === 1; // ini masih tanda tanya
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci !!!'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = ReqCathlab::select('nota as nota')->where('noreg', $request->noreg)
          ->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
