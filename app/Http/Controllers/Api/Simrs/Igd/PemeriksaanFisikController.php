<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaan_Psikologoldll;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemeriksaanFisikController extends Controller
{
    public function simpanpemeriksaanfisikigd(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        $kdgroupnakes = $user->kdgroupnakes;
        if ($request->id === '' || $request->id == null) {
            $cek = PemeriksaanUmum::leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs253.user')
            ->where('kepegx.pegawai.kdgroupnakes',$kdgroupnakes)->where('rs253.kdruang','POL014')->where('rs1', $request->noreg)->count();

            if($cek > 0)
            {
                return new JsonResponse(['message' => 'maaf entryan dokter sudah ada'],500);
            }

            try{
                DB::beginTransaction();
                    $simpan = PemeriksaanUmum::create(
                        [
                            'rs1' => $request->noreg,
                            'rs2' => $request->norm,
                            'rs3' => date('Y-m-d H:i:s'),
                            'rs4' => 'IRD',
                            'rs5' => $request->anatomikepala,
                            'rs6' => $request->anatomileher,
                            'rs7' => $request->anatomidada,
                            'rs8' => $request->anatomipunggung,
                            'rs9' => $request->anatomiperut,
                            'rs10' => $request->anatomitangan,
                            'rs11' => $request->anatomikaki,
                            'rs12' => $request->anatomineurologis,
                            'rs13' => $request->anatomigenital,
                            'kdruang' => 'POL014',
                            'user' => $kdpegsimrs
                        ]
                    );

                    $simpanx = Pemeriksaan_Psikologoldll::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_rs253' => $simpan->id,
                            'tgl' => date('Y-m-d H:i:s'),
                            'status_psikologis' => $request->statuspsikologi,
                            'status_psikologis_lain' => $request->sebutkanstatuspsikologis,
                            'sosial' => $request->sosial,
                            'ekonomi' => $request->ekonomi,
                            'spiritual' => $request->spiritual,
                            'nilai_kepercayaan' => $request->kepercayaan,
                            'ket_nilaikepercayaan' => $request->sebutkankepercayaan,
                            'keadaan_pupil' => $request->keadaanpupil,
                            'reflek_cahaya_kiri' => $request->reflekmatakirikecahaya,
                            'reflek_cahaya_kanan' => $request->reflekmatakanankecahaya,
                            'diameter_kiri' => $request->diamterkiri,
                            'diameter_kanan' => $request->diamterkanan,
                            'kd_ruang' => 'POL014',
                            'user' => $kdpegsimrs
                        ]
                    );

                    $hasil = PemeriksaanUmum::select('rs253.*','kepegx.pegawai.kdpegsimrs','kepegx.pegawai.nama')
                    ->with(
                        [
                            'pemerisaanpsikologidll'
                        ]
                    )->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs253.user')
                    ->where('rs1', $request->noreg)->where('kdruang','POL014')
                    ->orderBy('id','Desc')
                    ->get();

            DB::commit();

                    return new JsonResponse([
                        'message' => 'BERHASIL DISIMPAN',
                        'result' => $hasil
                    ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
            }

        }else{
            try{
                DB::beginTransaction();
                    $simpan = PemeriksaanUmum::where('id', $request->id)->update(
                        [
                            // 'rs1' => $request->noreg,
                            // 'rs2' => $request->norm,
                            // 'rs3' => date('Y-m-d H:i:s'),
                            // 'rs4' => 'IRD',
                            'rs5' => $request->anatomikepala,
                            'rs6' => $request->anatomileher,
                            'rs7' => $request->anatomidada,
                            'rs8' => $request->anatomipunggung,
                            'rs9' => $request->anatomiperut,
                            'rs10' => $request->anatomitangan,
                            'rs11' => $request->anatomikaki,
                            'rs12' => $request->anatomineurologis,
                            'rs13' => $request->anatomigenital,
                            // 'kdruang' => 'POL014',
                            // 'user' => $kdpegsimrs
                        ]
                    );

                    $simpanx = Pemeriksaan_Psikologoldll::where('id_rs253', $request->id)->update(
                        [
                            // 'noreg' => $request->noreg,
                            // 'norm' => $request->norm,
                            // 'id_rs253' => $simpan->id,
                            // 'tgl' => date('Y-m-d H:i:s'),
                            'status_psikologis' => $request->statuspsikologi,
                            'status_psikologis_lain' => $request->sebutkanstatuspsikologis,
                            'sosial' => $request->sosial,
                            'ekonomi' => $request->ekonomi,
                            'spiritual' => $request->spiritual,
                            'nilai_kepercayaan' => $request->kepercayaan,
                            'ket_nilaikepercayaan' => $request->sebutkankepercayaan,
                            'keadaan_pupil' => $request->keadaanpupil,
                            'reflek_cahaya_kiri' => $request->reflekmatakirikecahaya,
                            'reflek_cahaya_kanan' => $request->reflekmatakanankecahaya,
                            'diameter_kiri' => $request->diamterkiri,
                            'diameter_kanan' => $request->diamterkanan,
                            // 'kd_ruang' => 'POL014',
                            // 'user' => $kdpegsimrs
                        ]
                    );

                    $hasil = PemeriksaanUmum::select('rs253.*','kepegx.pegawai.kdpegsimrs','kepegx.pegawai.nama')
                    ->with(
                        [
                            'pemerisaanpsikologidll'
                        ]
                    )->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs253.user')
                    ->where('rs1', $request->noreg)->where('kdruang','POL014')
                    ->orderBy('id','Desc')
                    ->get();

                 DB::commit();

                    return new JsonResponse([
                        'message' => 'BERHASIL DISIMPAN',
                        'result' => $hasil
                    ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
            }
        }
    }

    public function hapuspemeriksaanfisik(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        if($kdpegsimrs !== $request->kdpegsimrs)
        {
            return new JsonResponse(['message' => 'Anda Tidak Berhak Menghapus, karena Bukan Anda Yang Menginput Data ini...!!!'], 500);
        }
        try{
            DB::beginTransaction();
            $dataheder = PemeriksaanUmum::where('id', $request->id);
            $datarinci = Pemeriksaan_Psikologoldll::where('id_rs253', $request->id);

            $hapusheder = $dataheder->delete();
            $hapusrinci = $datarinci->delete();

            $hasil = PemeriksaanUmum::select('rs253.*','kepegx.pegawai.kdpegsimrs','kepegx.pegawai.nama')
            ->with(
                [
                    'pemerisaanpsikologidll'
                ]
            )->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs253.user')
            ->where('rs253.rs1', $request->noreg)->where('rs253.kdruang','POL014')
            ->orderBy('rs253.id','Desc')
            ->get();

           DB::commit();

            return new JsonResponse([
                'message' => 'BERHASIL DIHAPUS',
                'result' => $hasil
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
