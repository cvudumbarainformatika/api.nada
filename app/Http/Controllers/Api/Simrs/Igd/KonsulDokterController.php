<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KonsulDokterController extends Controller
{
    public function simpandata(Request $request)
    {

      $dokter = Petugas::where('kdpegsimrs', $request->kddokterkonsul)->where('aktif', 'AKTIF')->first();

      if (!$dokter) {
        return new JsonResponse(['message' => 'Maaf Dokter Tidak Terdaftar di simrs'], 500);
      }

      $caritindakan = Mtindakan::where('rs1', 'T00075')->first();

      $user = FormatingHelper::session_user();
      $tglInput = date('Y-m-d H:i:s');

      $data=null;
      if ($request->has('id')) {
        $data = Konsultasi::find($request->id);
      } else {
        $data = new Konsultasi();
      }

      $cek = Konsultasi::where('kddokterkonsul',$request->kddokterkonsul)->where('noreg', $request->noreg)
      ->where('kdruang','POL014')->count();
      if($cek === 0){
        if($dokter['profesi'] === 'J00113' || $dokter['profesi'] === 'J00111' ){
            $data->noreg = $request->noreg;
            $data->norm = $request->norm;
            $data->rs140_id = '' ;
            $data->kddokterkonsul = $request->kddokterkonsul;
            $data->kduntuk = $request->kduntuk;
            $data->ketuntuk = $request->ketuntuk;
            $data->permintaan = $request->permintaan;
            $data->kdruang = $request->kodepoli;
            $data->tgl_permintaan = $tglInput;
            $data->kdminta = $user['kodesimrs'] ?? '';
            $data->user = $user['kodesimrs'] ?? '';
            $data->save();

            $hasil = Konsultasi::with(
                [
                    'tindakan' => function($tindakans){
                        $tindakans->with(
                            [
                                'mastertindakan'
                            ]
                        );
                    },
                    'nakesminta'
                ]
            )->where('kdruang', 'POL014')->where('noreg', $request->noreg)->orderBy('id','DESC')->get();

            return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $hasil], 200);
        }else{
            DB::select('call nota_tindakan(@nomor)');
            $x = DB::table('rs1')->select('rs14')->get();
            $wew = $x[0]->rs14;
            $notatindakan = FormatingHelper::notatindakan($wew, 'K-IG');

            $savetindakan = Tindakan::firstOrCreate(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $notatindakan,
                    'rs3' => $tglInput,
                    'rs4' => 'T00075',
                    'rs5' => '1',
                    'rs6' => $caritindakan['rs8'],
                    'rs7' => $caritindakan['rs8'],
                    'rs8' => $request->kddokterkonsul,
                    'rs9' => $user['kodesimrs'] ?? '',
                    'rs13' => $caritindakan['rs9'],
                    'rs14' => $caritindakan['rs9'],
                    //'rs20' => $request->noreg,
                    'rs22' => $request->kodepoli,
                ]
            );

            if (!$savetindakan) {
                return new JsonResponse(['message' => 'Maaf Tindakan Konsul Gagal Disimpan...!!!'], 500);
            }

            $data->noreg = $request->noreg;
            $data->norm = $request->norm;
            $data->rs140_id = $savetindakan->id ;
            $data->kddokterkonsul = $request->kddokterkonsul;
            $data->kduntuk = $request->kduntuk;
            $data->ketuntuk = $request->ketuntuk;
            $data->permintaan = $request->permintaan;
            $data->kdruang = $request->kodepoli;
            $data->tgl_permintaan = $tglInput;
            $data->kdminta = $user['kodesimrs'] ?? '';
            $data->user = $user['kodesimrs'] ?? '';
            $data->save();

            $hasil = Konsultasi::with(
                [
                    'tindakan' => function($tindakans){
                        $tindakans->with(
                            [
                                'mastertindakan'
                            ]
                        );
                    },
                    'nakesminta'
                ]
            )->where('kdruang', 'POL014')->where('noreg', $request->noreg)->orderBy('id','DESC')->get();

            return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $hasil], 200);
        }
      }else{

        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->kddokterkonsul = $request->kddokterkonsul;
        $data->kduntuk = $request->kduntuk;
        $data->ketuntuk = $request->ketuntuk;
        $data->permintaan = $request->permintaan;
        $data->kdruang = $request->kodepoli;
        $data->tgl_permintaan = $tglInput;
        $data->kdminta = $user['kodesimrs'] ?? '';
        $data->user = $user['kodesimrs'] ?? '';
        $data->save();

        $hasil = Konsultasi::with(
            [
                'tindakan' => function($tindakans){
                    $tindakans->with(
                        [
                            'mastertindakan'
                        ]
                    );
                },
                'nakesminta'
            ]
        )->where('kdruang', 'POL014')->where('noreg', $request->noreg)->orderBy('id','DESC')->get();

      return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $hasil], 200);
      }
    }

    public function hapusdata(Request $request)
    {
       $dokter = Mpegawaisimpeg::where('kdpegsimrs', $request->kddokterkonsul)->first();
    //    return $dokter;
       if($dokter['profesi'] === 'J00113' || $dokter['profesi'] === 'J00111'){
            $cek = Konsultasi::find($request->id);
            if (!$cek) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
            }

            $hapus = $cek->delete();
            if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
            }
            return new JsonResponse(['message' => 'berhasil dihapus'], 200);
       }else{
            $cek = Konsultasi::find($request->id);
            $idtindakan =  $cek['rs140_id'];
            if (!$cek) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
            }

            $hapus = $cek->delete();
            $cektindakan = Tindakan::find($idtindakan);
            $hapustindakan = $cektindakan->delete();

            if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
            }

            return new JsonResponse(['message' => 'berhasil dihapus'], 200);
       }
    }
}
