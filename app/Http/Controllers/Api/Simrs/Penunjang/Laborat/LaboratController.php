<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Laborat;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Laborat\MasterLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaboratController extends Controller
{
    public function listmasterpemeriksaanpoli()
    {
        $cito = request('cito');
        if ($cito == 1) {
            $listmasterpemeriksaanpoli = MasterLaborat::select(
                'rs1 as kode',
                'rs2 as pemeriksaan',
                'rs3 as hargasaranapoliumum',
                'rs4 as hargapelayananpoliumum',
                'rs5 as hargasaranapolispesialis',
                'rs6 as hargapelayananpolispesialis',
                'rs21 as gruper',
                'nilainormal',
                'satuan',
                'hidden'
            )->where('rs25', '1')
                ->where('rs1', '!=', 'LAB126')
                ->where('hidden', '!=', '1')
                ->orderBy('rs2')->get();

            return $listmasterpemeriksaanpoli;
        } else {

            $listmasterpemeriksaanpoli = MasterLaborat::select(
                'rs1 as kode',
                'rs2 as pemeriksaan',
                'rs3 as hargasaranapoliumum',
                'rs4 as hargapelayananpoliumum',
                'rs5 as hargasaranapolispesialis',
                'rs6 as hargapelayananpolispesialis',
                'rs21 as gruper',
                'nilainormal',
                'satuan',
                'hidden'
            )->where('rs25', '1')->orwhere('rs25', '')
                ->where('rs1', '!=', 'LAB126')
                ->where('hidden', '!=', '1')
                ->orderBy('rs2')->get();

            return $listmasterpemeriksaanpoli;
        }
    }

    public function simpanpermintaanlaborat(Request $request) // ini sudah gak dipake
    {
        // return $request->all();
        // if ($request->nota == '' || $request->nota == null) {
        DB::select('call nota_permintaanlab(@nomor)');
        $x = DB::table('rs1')->select('rs28')->get();
        $wew = $x[0]->rs28;
        $notapermintaanlab = FormatingHelper::formatallpermintaan($wew, 'J-LAB');

        $userid = FormatingHelper::session_user();
        $simpanpermintaanlaborat = LaboratMeta::create(
            [

                'nota' => $request->nota ?? $notapermintaanlab,
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'jenis_laborat' => $request->jenis_laborat,
                'tgl_order' => date('Y-m-d H:i:s'),
                'puasa_pasien' => $request->puasa_pasien,
                'tgl_permintaan' => date('Y-m-d H:i:s'),
                'dokter_pengirim' => auth()->user()->pegawai_id,
                'faskes_pengirim' => $request->faskes_pengirim,
                'unit_pengirim' => $request->unit_pengirim,
                'prioritas_pemeriksaan' => $request->prioritas_pemeriksaan,
                'diagnosa_masalah' => $request->diagnosa_masalah,
                'catatan_permintaan' => $request->catatan_permintaan,
                'metode_pengiriman_hasil' => $request->metode_pengiriman_hasil,
                'asal_sumber_spesimen' => $request->asal_sumber_spesimen,
                'jumlah_spesimen' => $request->jumlah_spesimen,
                'volume_spesimen_klinis' => $request->volume_spesimen_klinis,
                'cara_pengambilan_spesimen' => $request->cara_pengambilan_spesimen,
                'waktu_pengambilan_spesimen' => date('Y-m-d H:i:s'),
                'kondisi_spesimen_waktu_diambil' => $request->kondisi_spesimen_waktu_diambil,
                'waktu_fiksasi_spesimen' => date('Y-m-d H:i:s'),
                'cairan_fiksasi' => $request->cairan_fiksasi,
                'volume_cairan_fiksasi' => $request->volume_cairan_fiksasi,
                'petugas_pengambil_spesimen' => $userid['kodesimrs'],
                'petugas_penerima_spesimen' => $userid['kodesimrs'],
                'petugas_penganalisa' => $userid['kodesimrs'],
            ]
        );



        if (!$simpanpermintaanlaborat) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $data = $request->details;
        foreach ($data as $key => $value) {
            Laboratpemeriksaan::create(
                // ['rs2' => $request->nota ?? $notapermintaanlab, 'rs1' => $request->noreg, 'rs4' => $value['kode']],
                [
                    'rs1' => $request->noreg,
                    'rs2' => $simpanpermintaanlaborat->nota,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $value['kode'],
                    'rs5' => $request->jumlah,
                    'rs6' => $request->biaya_sarana,
                    'rs7' => $request->biaya_sarana,
                    'rs8' => $request->kodedokter,
                    'rs9' => $userid['kodesimrs'],
                    'rs12' => $request->prioritas_pemeriksaan === 'Iya' ? '1' : '',
                    'rs13' => $request->biaya_layanan,
                    'rs14' => $request->biaya_layanan,
                    'rs23'  => $request->unit_pengirim,
                    'rs24'  => $request->kdsistembayar
                ]
            );
        };

        $nota = LaboratMeta::select('nota')->where('noreg', $request->noreg)
            ->groupBy('nota')->orderBy('id', 'DESC')->get();



        return new JsonResponse(
            [
                'message' => 'Berhasil Order Ke Laborat',
                'result' => $simpanpermintaanlaborat->load(['details.pemeriksaanlab']),
                'nota' => $nota
            ],
            200
        );
        // return $simpanpermintaanlaborat;
        // }
    }

    public function simpanpermintaanlaboratbaru(Request $request)
    {
        // return $request->form;
        $cek = Laboratpemeriksaan::where('rs2', $request->nota)->where('rs18', '=', '1')->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Permintaan Sudah dikunci Oleh Laborat, Tidak bisa Tambah!'], 500);
        }

        $auth = Pegawai::find(auth()->user()->pegawai_id);
        $user = $auth->kdpegsimrs ?? '';
        $ruangan = $request->unit_pengirim;

        try {
            // begin transaction



            DB::beginTransaction();

            // write your dependent quires here
            DB::select('call nota_permintaanlab(@nomor)');
            $x = DB::table('rs1')->select('rs28')->get();
            $wew = $x[0]->rs28;
            $dari = null;
            if ($ruangan === 'POL014') {
                $dari = 'IGD';
                $notapermintaanlab = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'G-LAB');
            } else if ($ruangan === 'PEN005') {
                $dari = 'HD';
                $notapermintaanlab = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'H-LAB');
            } else {
                if ($request->isRanap === true) {
                    $dari = 'RANAP';
                    $notapermintaanlab = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'I-LAB');
                } else {
                    $dari = 'RAJAL';
                    $notapermintaanlab = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'J-LAB');
                }
            }

            // $thumb = [];
            // foreach ($request->form as $key => $value) {
            $where = [
                'nota' => $notapermintaanlab,
                'noreg' => $request->noreg,
                'norm' => $request->norm,
            ];
            $form = [
                'jenis_laborat' => $request->jenis_laborat ?? '',
                'tgl_order' => date('Y-m-d H:i:s'),
                'puasa_pasien' => $request->puasa_pasien ?? '',
                'tgl_permintaan' => date('Y-m-d H:i:s'),
                'dokter_pengirim' => $request->kodedokter ?? '',
                'faskes_pengirim' => $request->faskes_pengirim ?? '',
                'unit_pengirim' => $ruangan,
                'prioritas_pemeriksaan' => $request->prioritas_pemeriksaan ?? '',
                'diagnosa_masalah' => $request->diagnosa_masalah ?? '',
                'catatan_permintaan' => $request->catatan_permintaan ?? '',
                'metode_pengiriman_hasil' => $request->metode_pengiriman_hasil ?? '',
                'asal_sumber_spesimen' => $request->asal_sumber_spesimen,
                'jumlah_spesimen' => $request->jumlah_spesimen ?? '',
                'volume_spesimen_klinis' => $request->volume_spesimen_klinis ?? '',
                'cara_pengambilan_spesimen' => $request->cara_pengambilan_spesimen ?? '',
                'waktu_pengambilan_spesimen' => date('Y-m-d H:i:s'),
                'kondisi_spesimen_waktu_diambil' => $request->kondisi_spesimen_waktu_diambil ?? '',
                'waktu_fiksasi_spesimen' => date('Y-m-d H:i:s'),
                'cairan_fiksasi' => $request->cairan_fiksasi ?? '',
                'volume_cairan_fiksasi' => $request->volume_cairan_fiksasi ?? '',
                'petugas_pengambil_spesimen' => '',
                'petugas_penerima_spesimen' => '',
                'petugas_penganalisa' => '',
            ];



            $simpanpermintaanlaborat = LaboratMeta::firstOrCreate($where, $form);

            // array_push($thumb, $simpanpermintaanlaborat->nota);

            // if (!$simpanpermintaanlaborat) {
            //     throw new Exept('Custom exception!');
            // }

            if (!$simpanpermintaanlaborat) {
                return new JsonResponse(['message' => 'Header Data Gagal Disimpan'], 500);
            }


            $data = $request->details;
            // $rs51 = [];
            // foreach ($data as $row => $val) {
            //     $param = [
            //         'rs2' => $simpanpermintaanlaborat->nota,
            //         'rs1' => $request->noreg,
            //         'rs4' => $val['kode'],
            //         'rs3' => date('Y-m-d H:i:s'),
            //         'rs5' => $request->jumlah ?? '',
            //         'rs6' => $val['biaya_sarana'] ?? '',
            //         'rs7' => $val['biaya_sarana'] ?? '',
            //         'rs8' => $request->kodedokter ?? '',
            //         'rs9' => $user,
            //         'rs12' => $request->prioritas_pemeriksaan === 'Iya' ? '1' : '',
            //         'rs13' => $val['biaya_layanan'] ?? '',
            //         'rs14' => $val['biaya_layanan'] ?? '',
            //         'rs23'  => $ruangan,
            //         'rs24'  => $request->kdsistembayar ?? ''
            //     ];
            //     // Laboratpemeriksaan::create($param);
            //     $rs51[] = $param;
            // };
            // Laboratpemeriksaan::insert($rs51);
            // } // END FOREACH
            /**
             * simpan rincian baru
             */
            foreach ($data as $row => $val) {
                Laboratpemeriksaan::firstOrCreate(
                    [
                        'rs2' => $simpanpermintaanlaborat->nota,
                        'rs1' => $request->noreg,
                        'rs4' => $val['kode'],
                    ],
                    [
                        'rs3' => date('Y-m-d H:i:s'),
                        'rs5' => $request->jumlah ?? '',
                        'rs6' => $val['biaya_sarana'] ?? '',
                        'rs7' => $val['biaya_sarana'] ?? '',
                        'rs8' => $request->kodedokter ?? '',
                        'rs9' => $user,
                        'rs12' => $request->prioritas_pemeriksaan === 'Iya' ? '1' : '',
                        'rs13' => $val['biaya_layanan'] ?? '',
                        'rs14' => $val['biaya_layanan'] ?? '',
                        'rs23'  => $ruangan,
                        'rs24'  => $request->kdsistembayar ?? ''
                    ]
                );
                // Laboratpemeriksaan::create($param);

            };
            /**
             * simpan rincian baru end
             */


            DB::commit();

            $success = LaboratMeta::where('nota', $notapermintaanlab)->with(['details.pemeriksaanlab'])->get();
            $nota = LaboratMeta::select('nota')->where('noreg', $request->noreg)
                ->groupBy('nota')->orderBy('id', 'DESC')->get();

            // kirim ke notif
            $msg = [
                'data' => [
                    'menu' => 'permintaan-laborat',
                    'title' => 'Permintaan Baru',
                    'dari' => $dari,
                    'cito' => $request->prioritas_pemeriksaan,
                    'read' => false
                ]
            ];

            try {
                event(new NotifMessageEvent($msg, 'permintaan-laborat', auth()->user()));
            } catch (\Exception $e) {
                Log::error("Event permintaan-laborat gagal dijalankan: " . $e->getMessage());
            }

            return new JsonResponse(
                [
                    'message' => 'Berhasil Order Ke Laborat',
                    'result' => $success,
                    'nota' => $nota
                ],
                200
            );

            // return $thumb;
        } catch (\Exception $e) {
            // May day,  rollback!!! rollback!!!
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $e->getMessage()], 500);
        }




        // return $thumb;
    }

    public function getnota()
    {
        $nota = LaboratMeta::select('nota')

            ->where('noreg', request('noreg'))
            ->where(function ($query) {
                if (request('isRanap') === true || request('isRanap') === 'true') {
                    $query->where('unit_pengirim', '!=', 'POL014');
                }
                // else{
                //     $query->where('unit_pengirim', '=', 'POL014');
                // }
            })
            ->groupBy('nota')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function getnotaIgd()
    {
        $nota = LaboratMeta::select('nota')->where('noreg', request('noreg'))->where('unit_pengirim', 'POL014')
            ->groupBy('nota')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function getnotaoldIgd()
    {
        $nota = Laboratpemeriksaan::select('rs2')->where('rs1', request('noreg'))->where('rs23', 'POL014')
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function getnotaold()
    {
        $nota = Laboratpemeriksaan::select('rs2')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function getdataIgd()
    {
        $data = LaboratMeta::select('*')->where('noreg', request('noreg'))->where('unit_pengirim', 'POL014')
            ->with('details.pemeriksaanlab')->orderBy('id', 'DESC')->get();
        return new JsonResponse($data);
    }


    public function getdata()
    {
        $data = LaboratMeta::select('*')

            ->where('noreg', request('noreg'))
            ->where(function ($query) {
                if (request('isRanap') === true || request('isRanap') === 'true') {
                    $query->where('unit_pengirim', '!=', 'POL014');
                }
            })
            ->with('details.pemeriksaanlab')
            ->orderBy('id', 'DESC')->get();
        return new JsonResponse($data);
    }

    public function hapuspermintaanlaborat(Request $request)
    {
        $cari = LaboratMeta::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapusdetail = Laboratpemeriksaan::where('rs2', '=', $cari->nota)->delete();
        $hapus = $cari->delete();
        $nota = LaboratMeta::select('nota')->where('noreg', $request->noreg)
            ->groupBy('nota')->orderBy('id', 'DESC')->get();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
    public function hapuspermintaanlaboratbaru(Request $request)
    {
        $cek = Laboratpemeriksaan::whereIn('id', $request->id)->where('rs18', '=', '1')->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Permintaan Sudah dikunci Oleh Laborat, Tidak bisa dihapus!'], 500);
        }

        $hapus = Laboratpemeriksaan::whereIn('id', $request->id)->delete();
        $data = LaboratMeta::where('noreg', $request->noreg)->with(['details.pemeriksaanlab'])->get();

        $collection = collect($data);
        $nota = $collection->pluck('nota');
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse([
            'message' => 'berhasil dihapus',
            'result' => $data,
            'nota' => $nota,
        ], 200);
    }

    public function hapuspermintaanlaboratbaruIgd(Request $request)
    {
        $cek = Laboratpemeriksaan::whereIn('id', $request->id)->where('rs18', '=', '1')->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Permintaan Sudah dikunci Oleh Laborat, Tidak bisa dihapus!'], 500);
        }

        $hapus = Laboratpemeriksaan::whereIn('id', $request->id)->delete();
        $data = LaboratMeta::where('noreg', $request->noreg)->with(['details.pemeriksaanlab'])->where('unit_pengirim', 'POL014')->get();

        $collection = collect($data);
        $nota = $collection->pluck('nota');
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse([
            'message' => 'berhasil dihapus',
            'result' => $data,
            'nota' => $nota,
        ], 200);
    }

    public function simpanpermintaanlaboratbaruIgd(Request $request)
    {
        // return $request->form;
        $cek = Laboratpemeriksaan::where('rs2', $request->nota)->where('rs18', '=', '1')->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Permintaan Sudah dikunci Oleh Laborat, Tidak bisa Tambah!'], 500);
        }

        $auth = Pegawai::find(auth()->user()->pegawai_id);
        $user = $auth->kdpegsimrs ?? '';
        $ruangan = $request->unit_pengirim;

        try {
            // begin transaction



            DB::beginTransaction();

            // write your dependent quires here
            DB::select('call nota_permintaanlab(@nomor)');
            $x = DB::table('rs1')->select('rs28')->get();
            $wew = $x[0]->rs28;
            $dari = null;
            if ($ruangan === 'POL014') {
                $dari = 'IGD';
                $notapermintaanlab = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'G-LAB');
            } else {
                if ($request->isRanap === true) {
                    $dari = 'RANAP';
                    $notapermintaanlab = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'I-LAB');
                } else {
                    $dari = 'RAJAL';
                    $notapermintaanlab = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'J-LAB');
                }
            }

            // $thumb = [];
            // foreach ($request->form as $key => $value) {
            $where = [
                'nota' => $notapermintaanlab,
                'noreg' => $request->noreg,
                'norm' => $request->norm,
            ];
            $form = [
                'jenis_laborat' => $request->jenis_laborat ?? '',
                'tgl_order' => date('Y-m-d H:i:s'),
                'puasa_pasien' => $request->puasa_pasien ?? '',
                'tgl_permintaan' => date('Y-m-d H:i:s'),
                'dokter_pengirim' => $request->kodedokter ?? '',
                'faskes_pengirim' => $request->faskes_pengirim ?? '',
                'unit_pengirim' => $ruangan,
                'prioritas_pemeriksaan' => $request->prioritas_pemeriksaan ?? '',
                'diagnosa_masalah' => $request->diagnosa_masalah ?? '',
                'catatan_permintaan' => $request->catatan_permintaan ?? '',
                'metode_pengiriman_hasil' => $request->metode_pengiriman_hasil ?? '',
                'asal_sumber_spesimen' => $request->asal_sumber_spesimen,
                'jumlah_spesimen' => $request->jumlah_spesimen ?? '',
                'volume_spesimen_klinis' => $request->volume_spesimen_klinis ?? '',
                'cara_pengambilan_spesimen' => $request->cara_pengambilan_spesimen ?? '',
                'waktu_pengambilan_spesimen' => date('Y-m-d H:i:s'),
                'kondisi_spesimen_waktu_diambil' => $request->kondisi_spesimen_waktu_diambil ?? '',
                'waktu_fiksasi_spesimen' => date('Y-m-d H:i:s'),
                'cairan_fiksasi' => $request->cairan_fiksasi ?? '',
                'volume_cairan_fiksasi' => $request->volume_cairan_fiksasi ?? '',
                'petugas_pengambil_spesimen' => '',
                'petugas_penerima_spesimen' => '',
                'petugas_penganalisa' => '',
            ];



            $simpanpermintaanlaborat = LaboratMeta::firstOrCreate($where, $form);

            // array_push($thumb, $simpanpermintaanlaborat->nota);

            // if (!$simpanpermintaanlaborat) {
            //     throw new Exept('Custom exception!');
            // }

            if (!$simpanpermintaanlaborat) {
                return new JsonResponse(['message' => 'Header Data Gagal Disimpan'], 500);
            }


            $data = $request->details;
            foreach ($data as $row => $val) {
                Laboratpemeriksaan::firstOrCreate(
                    [
                        'rs2' => $simpanpermintaanlaborat->nota,
                        'rs1' => $request->noreg,
                        'rs4' => $val['kode'],
                    ],
                    [
                        'rs3' => date('Y-m-d H:i:s'),
                        'rs5' => $request->jumlah ?? '',
                        'rs6' => $val['biaya_sarana'] ?? '',
                        'rs7' => $val['biaya_sarana'] ?? '',
                        'rs8' => $request->kodedokter ?? '',
                        'rs9' => $user,
                        'rs12' => $request->prioritas_pemeriksaan === 'Iya' ? '1' : '',
                        'rs13' => $val['biaya_layanan'] ?? '',
                        'rs14' => $val['biaya_layanan'] ?? '',
                        'rs23'  => $ruangan,
                        'rs24'  => $request->kdsistembayar ?? ''
                    ]
                );
                // Laboratpemeriksaan::create($param);

            };
            /**
             * simpan rincian baru end
             */


            DB::commit();

            $success = LaboratMeta::where('nota', $notapermintaanlab)->where('unit_pengirim', 'POL014')->with(['details.pemeriksaanlab'])->get();
            $nota = LaboratMeta::select('nota')->where('noreg', $request->noreg)->where('unit_pengirim', 'POL014')
                ->groupBy('nota')->orderBy('id', 'DESC')->get();

            // kirim ke notif
            $msg = [
                'data' => [
                    'menu' => 'permintaan-laborat',
                    'title' => 'Permintaan Baru',
                    'dari' => $dari,
                    'cito' => $request->prioritas_pemeriksaan,
                    'read' => false
                ]
            ];

            try {
                event(new NotifMessageEvent($msg, 'permintaan-laborat', auth()->user()));
            } catch (\Exception $e) {
                Log::error("Event permintaan-laborat gagal dijalankan: " . $e->getMessage());
            }

            return new JsonResponse(
                [
                    'message' => 'Berhasil Order Ke Laborat',
                    'result' => $success,
                    'nota' => $nota
                ],
                200
            );

            // return $thumb;
        } catch (\Exception $e) {
            // May day,  rollback!!! rollback!!!
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $e->getMessage()], 500);
        }




        // return $thumb;
    }


    public function coba_notif()
    {

        $msg = [
            'data' => [
                'menu' => 'permintaan-laborat',
                'title' => 'Permintaan Baru',
                'dari' => 'RANAP',
                'cito' => 'Iya',
                'read' => false
            ]
        ];
        try {
            event(new NotifMessageEvent($msg, 'permintaan-laborat', auth()->user()));
        } catch (\Exception $e) {
            Log::error("Event permintaan-laborat gagal dijalankan: " . $e->getMessage());
        }

        return new JsonResponse(['message' => 'berhasil dikirim'], 200);
    }
}
