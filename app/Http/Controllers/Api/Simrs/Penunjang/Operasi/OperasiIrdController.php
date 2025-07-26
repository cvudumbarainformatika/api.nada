<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Operasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Operasi\PermintaanOperasiIrd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperasiIrdController extends Controller
{
    public function getnota()
    {
        $nota = PermintaanOperasiIrd::select('rs2 as nota')->where('rs1', request('noreg'))
            ->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }
    public function getdata()
    {
        $data = PermintaanOperasiIrd::select('*')->where('rs1', request('noreg'))
        ->with('petugas:kdpegsimrs,nik,nama,kdgroupnakes')
        ->orderBy('id', 'DESC')->get();
        return new JsonResponse($data);
    }

    public function simpandata(Request $request)
    {

      $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

      if (count($cekKasir) > 0) {
        return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
      }

      DB::select('call nota_permintaanbedah(@nomor)');
      $x = DB::table('rs1')->select('rs27')->get();
      $wew = $x[0]->rs27;

      $nota = $request->nota ?? FormatingHelper::formatallpermintaan($wew, '/POK-RI');

      $userid = FormatingHelper::session_user();
      $simpan = PermintaanOperasiIrd::firstOrCreate(
          [
              'rs1' => $request->noreg,
              'rs2' => $nota,
          ],
          [
              'rs3' => date('Y-m-d H:i:s'),
              'rs4' => $request->permintaan,
              'rs8' => $request->kodedokter, //$request->kodedokter
              'rs9' => '1',
              'rs10' => $request->kodepoli, // ruangan
              'rs11' => $userid['kodesimrs'],
              'rs13' => $request->kdgroup_ruangan, // group_ruangan
              'rs14' => $request->kodesistembayar, //$request->kd_akun
              'rs15' => date('Y-m-d H:i:s'),
              // 'cito' => $request->cito === 'Iya' ? 'Ya' : '',
              // 'jenis_pemeriksaan' => '',
              // 'kddokterpengirim' => '',
              // 'faskespengirim' => '',
              // 'unitpengirim' => '',
              // 'diagnosakerja' => $request->diagnosakerja ?? '',
              // 'catatanpermintaan' => $request->catatanpermintaan ?? '',
              // 'metodepenyampaianhasil' => $request->metodepenyampaianhasil ?? '',
              // 'statusalergipasien' => $request->statusalergipasien ?? '',
              // 'statuskehamilan' => $request->statuskehamilan ?? '',
          ]
      );

      if (!$simpan) {
          return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
      }
      // return ($simpanpermintaanradiologi);
      $nota = PermintaanOperasiIrd::select('rs2 as nota')->where('rs1', $request->noreg)
          ->groupBy('rs2')->orderBy('id', 'DESC')->get();

      return new JsonResponse(
          [
              'message' => 'Permintaan Operasi Berhasil di Simpan',
              'result' => $simpan->load('petugas:kdpegsimrs,nama,nik,kdgroupnakes'),
              'nota' => $nota
          ],
          200
      );
    }

    public function hapusdata(Request $request)
    {
        $cari = PermintaanOperasiIrd::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->rs12 === '1';
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = PermintaanOperasiIrd::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
