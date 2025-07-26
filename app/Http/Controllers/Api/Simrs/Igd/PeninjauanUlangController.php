<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Rajal\Igd\Tinjauan_ulang;
use App\Models\Simrs\Rajal\Igd\Tinjauan_ulang_bps;
use App\Models\Simrs\Rajal\Igd\Tinjauan_ulang_nips;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeninjauanUlangController extends Controller
{
    public function simpanpeninjauanulang(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        if($request->has('id')){
            try{
                DB::beginTransaction();
               // return $request->id;
                $simpan = Tinjauan_ulang::where('id', $request->id)->update(
                    [
                        'keluhan' => $request->keluhan,
                        'nadi' => $request->nadi,
                        'pernapasanx' => $request->pernapasanx,
                        'sistole' => $request->sistole,
                        'diastole' => $request->diastole,
                        'suhu' => $request->suhu,
                        'spo2' => $request->spo2,
                        'scorenadi' => $request->scorenadi,
                        'scorepernapasanx' => $request->scorepernapasanx,
                        'scoresistole' => $request->scoresistole,
                        'scorediastole' => $request->scorediastole,
                        'scoresuhu' => $request->scoresuhu,
                        'scorespo2' => $request->scorespo2,
                        'kesadaran' => $request->kesadaran,
                        'eye' => $request->eye,
                        'verbal' => $request->verbal,
                        'motorik' => $request->motorik,
                        'keadaan_pupil' => $request->keadaanpupil,
                        'reflekcahaya_matakanan' => $request->reflekmatakanankecahaya,
                        'reflekcahaya_matakiri' => $request->reflekmatakirikecahaya,
                        'diamter_matakanan' => $request->diamterkanan,
                        'diamter_matakiri' => $request->diamterkiri,
                        'output' => $request->output,
                        'skornyeri' => $request->skornyeri,
                        'keteranganscorenyeri' => $request->keteranganscorenyeri,
                        'keterangan' => $request->keterangan,
                        'kdruang' => 'POL014',
                        'metodenyeri' => $request->metodenyeri,
                        'user' => $kdpegsimrs
                    ]
                );

                if($request->metodenyeri === 'nips')
                    {
                        $simpannips=Tinjauan_ulang_nips::where('id_heder', $request->id)->update(
                            [
                                'ekspresiwajahnips' => $request->ekspresiwajahnips,
                                'menangis' => $request->menangis,
                                'polanafas' => $request->polanafas,
                                'lengan' => $request->lengan,
                                'kaki' => $request->kaki,
                                'keadaanrangsangan' => $request->keadaanrangsangan,
                                'skor' => $request->scroenips,
                                'ket_skor' => $request->ketscorenips,
                            ]
                        );
                    }else if($request->metodenyeri === 'bps')
                    {
                        $simpanbps= Tinjauan_ulang_bps::where('id_heder', $request->id)->update(
                            [
                                'ekspresiwajah' => $request->ekspresiwajahbps,
                                'gerakantangan' => $request->gerakantangan,
                                'kepatuhanventilasimekanik' =>$request->kepatuhanventilasimekanik,
                                'scroebps' =>$request->scroebps,
                                'ketscorebps' =>$request->ketscorebps,
                            ]
                        );
                    }

                $hasil = Tinjauan_ulang::with([
                    'tinjauanulangnips',
                    'tinjauanulangbps'
                ])->where('noreg', $request->noreg)->orderBy('id','Desc')->limit(1)->get();
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
                $simpan = Tinjauan_ulang::create(
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'tgl' => date('Y-m-d H:i:s'),
                        'keluhan' => $request->keluhan,
                        'nadi' => $request->nadi,
                        'pernapasanx' => $request->pernapasanx,
                        'sistole' => $request->sistole,
                        'diastole' => $request->diastole,
                        'suhu' => $request->suhu,
                        'spo2' => $request->spo2,
                        'scorenadi' => $request->scorenadi,
                        'scorepernapasanx' => $request->scorepernapasanx,
                        'scoresistole' => $request->scoresistole,
                        'scorediastole' => $request->scorediastole,
                        'scoresuhu' => $request->scoresuhu,
                        'scorespo2' => $request->scorespo2,
                        'kesadaran' => $request->kesadaran,
                        'eye' => $request->eye,
                        'verbal' => $request->verbal,
                        'motorik' => $request->motorik,
                        'keadaan_pupil' => $request->keadaanpupil,
                        'reflekcahaya_matakanan' => $request->reflekmatakanankecahaya,
                        'reflekcahaya_matakiri' => $request->reflekmatakirikecahaya,
                        'diamter_matakanan' => $request->diamterkanan,
                        'diamter_matakiri' => $request->diamterkiri,
                        'output' => $request->output,
                        'skornyeri' => $request->skornyeri,
                        'keteranganscorenyeri' => $request->keteranganscorenyeri,
                        'keterangan' => $request->keterangan,
                        'kdruang' => 'POL014',
                        'metodenyeri' => $request->metodenyeri,
                        'user' => $kdpegsimrs
                    ]
                );

                if($request->metodenyeri === 'nips')
                {
                    $simpannips=Tinjauan_ulang_nips::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_heder' => $simpan->id,
                            'ekspresiwajahnips' => $request->ekspresiwajahnips,
                            'menangis' => $request->menangis,
                            'polanafas' => $request->polanafas,
                            'lengan' => $request->lengan,
                            'kaki' => $request->kaki,
                            'keadaanrangsangan' => $request->keadaanrangsangan,
                            'skor' => $request->scroenips,
                            'ket_skor' => $request->ketscorenips,
                            'user' => $kdpegsimrs
                        ]
                    );
                }else if($request->metodenyeri === 'bps')
                {
                    $simpanbps= Tinjauan_ulang_bps::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_heder' => $simpan->id,
                            'ekspresiwajah' => $request->ekspresiwajahbps,
                            'gerakantangan' => $request->gerakantangan,
                            'kepatuhanventilasimekanik' =>$request->kepatuhanventilasimekanik,
                            'scroebps' =>$request->scroebps,
                            'ketscorebps' =>$request->ketscorebps,
                            'user' => $kdpegsimrs
                        ]
                    );
                }

                $hasil = Tinjauan_ulang::with([
                    'tinjauanulangnips',
                    'tinjauanulangbps'
                ])->where('noreg', $request->noreg)->orderBy('id','Desc')->limit(1)->get();

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
        return $request->has('id');

    }

    public function hapuspeninjauanulang(Request $request)
    {

        $data = Tinjauan_ulang::find($request->id);
        $datax = Tinjauan_ulang_nips::where('id_heder',$request->id);
        $dataxx = Tinjauan_ulang_bps::where('id_heder',$request->id);

        $hapus = $data->delete();
        $hapusx = $datax->delete();
        $hapusxx = $dataxx->delete();
        return new JsonResponse([
            'message' => 'BERHASIL DIHAPUS...!!!'
        ], 200);
    }
}
