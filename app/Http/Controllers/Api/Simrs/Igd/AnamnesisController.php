<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\AnamnesisBps;
use App\Models\Simrs\Anamnesis\AnamnesisNips;
use App\Models\Simrs\Anamnesis\AnamnesisTambahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnamnesisController extends Controller
{
    public function simpananamnesis(Request $request)
    {
        // return ('wew');
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        $kdgroupnakes = $user->kdgroupnakes;

        if ($request->has('id')) {

            $data = Anamnesis::where('id', $request->id)->update(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    // 'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                    'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                    'skreeninggizi' => $request->skreeninggizi ?? 0,
                    'asupanmakan' => $request->asupanmakan ?? 0,
                    'kondisikhusus' => $request->kondisikhusus ?? '',
                    'skor' => $request->skor ?? 0,
                    // 'scorenyeri' => $request->skornyeri ?? 0,
                    // 'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                    'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->riwayatpekerjaan,
                    //    'keteranganscorenyeri' => $request->riwayatpekerjaan ?? '',
                    'user'  => $kdpegsimrs,
                ]
            );
            // if ($hasil === 1) {
            //     $simpananamnesis = Anamnesis::where('id', $request->id)->first();
            // } else {
            //     $simpananamnesis = null;
            // }

            $update = AnamnesisTambahan::where('id_heder', $request->id)->update(
                [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'id_heder' => $request->id,
                    'lokasi_nyeri' => $request->lokasinyeri,
                    'durasi_nyeri' => $request->durasinyeri,
                    'penyebab_nyeri' => $request->penyebabnyeri,
                    'frekwensi_nyeri' => $request->frekwensinyeri,
                    'nyeri_hilang' => $request->nyerihilang,
                    'sebutkannyerihilang' => $request->sebutkannyerihilang,
                    'aktifitas_mobilitas' => $request->aktivitasmobilitas,
                    'sebutkanperlubanuan' => $request->sebutkanperlubanuan,
                    'alat_bantu_jalan' => $request->aktivitasAlatBnatujalan,
                    'sebutkanalatbantujalan' => $request->sebutkanalatbantujalan,
                    'bicara' => $request->kebutuhankomunikasidanedukasi,
                    'sebutkankomunaksilainnya' => $request->sebutkankomunaksilainnya,
                    'penerjemah' => $request->penerjemah,
                    'sebutkanpenerjemah' => $request->sebutkanpenerjemah,
                    'bahasa_isyarat' => $request->bahasaisyarat,
                    'hambatan' => $request->hamabatan,
                    'sebutkanhambatan' => $request->sebutkanhambatan,
                    'riwayat_demam' => $request->riwayatdemam,
                    'berkeringat_malam_hari' => $request->berkeringat,
                    'riwayat_bepergian' => $request->riwayatbepergian,
                    'riwayat_pemakaian_obat' => $request->obatjangkapanjang,
                    'riwayat_bb_turun' => $request->bbturun,
                    'kdruang' => 'POL014',
                    'user' => $kdpegsimrs,
                ]
            );

            if($request->metode === 'bps')
            {
                $updatebps = AnamnesisBps::create(
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'id_heder' => $request->id,
                        'ekspresi_wajah' => $request->ekspresiwajah,
                        'gerakan_tangan' => $request->gerakantangan,
                        'kepatuhan_ventilasi_mekanik' => $request->kepatuhanventilasimekanik,
                        'skor' => $request->scroebps,
                        'keterangan_skor' => $request->ketscorebps,
                        'ruangan' => 'POL014',
                        'user' => $kdpegsimrs,

                    ]
                );
                $carimips = AnamnesisNips::where('id_heder',$request->id);
                $hapusnips = $carimips->delete();

                $updatescorenyeri = Anamnesis::where('id', $request->id)->update(
                    [

                        'scorenyeri' => '',
                        'keteranganscorenyeri' => '',

                    ]
                );

            }else if($request->metode === 'nips')
            {
                $updatenips = AnamnesisNips::create(
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'id_heder' => $request->id,
                        'ekspresi_wajah' => $request->ekspresiwajahnips,
                        'menangis' => $request->menangis,
                        'pola_nafas' => $request->polanafas,
                        'lengan' => $request->lengan,
                        'kaki' => $request->kaki,
                        'keadaan_rangsangan' => $request->keadaanrangsangan,
                        'skor' => $request->scroenips,
                        'ket_skor' => $request->ketscorenips,
                        'ruangan' => 'POL014',
                        'user' => $kdpegsimrs,

                    ]
                );
                $caribps = AnamnesisBps::where('id_heder',$request->id);
                $hapusbps = $caribps->delete();

                $updatescorenyeri = Anamnesis::where('id', $request->id)->update(
                    [

                        'scorenyeri' => '',
                        'keteranganscorenyeri' =>'',

                    ]
                );

            }else{
                $updatescorenyeri = Anamnesis::where('id', $request->id)->update(
                    [

                        'scorenyeri' => $request->skornyeri ?? 0,
                        'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',

                    ]
                );
                $carimips = AnamnesisNips::where('id_heder',$request->id);
                $hapusnips = $carimips->delete();

                $caribps = AnamnesisBps::where('id_heder',$request->id);
                $hapusbps = $caribps->delete();
            }

            // $hasil = Anamnesis::select('rs209.*')->with(
            //     [
            //         'anamnesetambahan','anamnesebps','anamnesenips','datasimpeg'
            //     ]
            // )->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs209.user')
            // ->where('rs209.rs1', $request->noreg)
            // ->where('kdruang', 'POL014')
            // ->limit(1)
            // ->orderBy('rs209.id','Desc')
            // ->get();

            $hasil = Anamnesis::with(
                    [
                        'anamnesetambahan','anamnesebps','anamnesenips','datasimpeg'
                    ]
                )->where('id', $request->id)
                ->where('kdruang', 'POL014')
                ->limit(1)
                ->orderBy('id','Desc')
                ->get();



            return new JsonResponse([
                'message' => 'BERHASIL DISIMPANx',
                'result' => $hasil
            ], 200);

        } else {
            $cek = Anamnesis::leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs209.user')
                ->where('kepegx.pegawai.kdgroupnakes',$kdgroupnakes)->where('rs209.kdruang','POL014')->where('rs1', $request->noreg)->count();

            if($cek > 0)
            {
                return new JsonResponse(['message' => 'maaf entryan dokter sudah ada'],500);
            }

            try{
                DB::beginTransaction();
                $simpananamnesis = Anamnesis::create(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->norm,
                        'rs3' => date('Y-m-d H:i:s'),
                        'rs4' => $request->keluhanutama,
                        'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                        'riwayatalergi' => $request->riwayatalergi ?? '',
                        'keteranganalergi' => $request->keteranganalergi ?? '',
                        'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                        'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                        'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                        'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->riwayatpekerjaan,
                        'skreeninggizi' => $request->skreeninggizi ?? 0,
                        'asupanmakan' => $request->asupanmakan ?? 0,
                        'kondisikhusus' => $request->kondisikhusus ?? '',
                        'skor' => $request->skor ?? 0,
                        'scorenyeri' => $request->skornyeri ?? 0,
                        'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                        'kdruang' => 'POL014',
                        'user'  => $kdpegsimrs,
                    ]
                );

                if (!$simpananamnesis) {
                    return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
                }

                $simpansambungan = AnamnesisTambahan::create(
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'id_heder' => $simpananamnesis->id,
                        'lokasi_nyeri' => $request->lokasinyeri,
                        'durasi_nyeri' => $request->durasinyeri,
                        'penyebab_nyeri' => $request->penyebabnyeri,
                        'frekwensi_nyeri' => $request->frekwensinyeri,
                        'nyeri_hilang' => $request->nyerihilang,
                        'sebutkannyerihilang' => $request->sebutkannyerihilang,
                        'aktifitas_mobilitas' => $request->aktivitasmobilitas,
                        'sebutkanperlubanuan' => $request->sebutkanperlubanuan,
                        'alat_bantu_jalan' => $request->aktivitasAlatBnatujalan,
                        'sebutkanalatbantujalan' => $request->sebutkanalatbantujalan,
                        'bicara' => $request->kebutuhankomunikasidanedukasi,
                        'sebutkankomunaksilainnya' => $request->sebutkankomunaksilainnya,
                        'penerjemah' => $request->penerjemah,
                        'sebutkanpenerjemah' => $request->sebutkanpenerjemah,
                        'bahasa_isyarat' => $request->bahasaisyarat,
                        'hambatan' => $request->hamabatan,
                        'sebutkanhambatan' => $request->sebutkanhambatan,
                        'riwayat_demam' => $request->riwayatdemam,
                        'berkeringat_malam_hari' => $request->berkeringat,
                        'riwayat_bepergian' => $request->riwayatbepergian,
                        'riwayat_pemakaian_obat' => $request->obatjangkapanjang,
                        'riwayat_bb_turun' => $request->bbturun,
                        'kdruang' => 'POL014',
                        'user' => $kdpegsimrs,
                    ]
                );

                if($request->metode === 'bps')
                {
                    $simpanbps = AnamnesisBps::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_heder' => $simpananamnesis->id,
                            'ekspresi_wajah' => $request->ekspresiwajah,
                            'gerakan_tangan' => $request->gerakantangan,
                            'kepatuhan_ventilasi_mekanik' => $request->kepatuhanventilasimekanik,
                            'skor' => $request->scroebps,
                            'keterangan_skor' => $request->ketscorebps,
                            'ruangan' => 'POL014',
                            'user' => $kdpegsimrs,

                        ]
                    );

                }

                if($request->metode === 'nips')
                {
                    $simpannips = AnamnesisNips::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_heder' => $simpananamnesis->id,
                            'ekspresi_wajah' => $request->ekspresiwajahnips,
                            'menangis' => $request->menangis,
                            'pola_nafas' => $request->polanafas,
                            'lengan' => $request->lengan,
                            'kaki' => $request->kaki,
                            'keadaan_rangsangan' => $request->keadaanrangsangan,
                            'skor' => $request->scroenips,
                            'ket_skor' => $request->ketscorenips,
                            'ruangan' => 'POL014',
                            'user' => $kdpegsimrs,

                        ]
                    );
                }

                $hasil = Anamnesis::with(
                    [
                        'anamnesetambahan','anamnesebps','anamnesenips','datasimpeg'
                    ]
                )->where('rs1', $request->noreg)
                ->where('kdruang', 'POL014')
                ->limit(1)
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

    public function hapusanamnesis(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        if($kdpegsimrs !== $request->user)
        {
            return new JsonResponse(['message' => 'Anda Tidak Berhak Menghapus, karena Bukan Anda Yang Menginput Data ini...!!!'], 500);
        }

        try{
            DB::beginTransaction();
            $dataanamnesis = Anamnesis::where('id', $request->id);
            $dataanamnesistambahan = AnamnesisTambahan::where('id_heder', $request->id);
            $dataanamnesisbps = AnamnesisBps::where('id_heder', $request->id);
            $dataanamnesisnips = AnamnesisNips::where('id_heder', $request->id);

            $hapusanamnesis = $dataanamnesis->delete();
            $hapusanamnesistambahan = $dataanamnesistambahan->delete();
            $hapusanamnesisbps = $dataanamnesisbps->delete();
            $hapusanamnesisnips = $dataanamnesisnips->delete();

            $hasil = Anamnesis::with(
                [
                    'anamnesetambahan','anamnesebps','anamnesenips'
                ]
            )->where('rs1', $request->noreg)
            ->where('kdruang', 'POL014')
            ->orderBy('id','Desc')
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

    public function listanamnesebynoreg()
    {
        $hasil = Anamnesis::with(
            [
                'anamnesetambahan','anamnesebps','anamnesenips'
            ]
        )->where('rs1', request('noreg'))
        ->where('kdruang', 'POL014')
        ->limit(1)
        ->orderBy('id','Desc')
        ->get();

        return new JsonResponse($hasil);
    }

}
