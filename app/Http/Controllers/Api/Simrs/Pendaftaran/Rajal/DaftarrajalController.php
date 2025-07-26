<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Antrian\AntrianController;
use App\Http\Controllers\Api\Simrs\Igd\PlannController;
use App\Http\Controllers\Api\Simrs\Planing\PlaningController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Sigarang\Transaksi\Retur\Retur;
use App\Models\Simrs\Master\Mcounter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsantrian;
use App\Models\Simrs\Pendaftaran\Karcispoli;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianambil;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Logantrian;
use App\Models\Simrs\Pendaftaran\Rajalumum\Mjknantrian;
use App\Models\Simrs\Rajal\Listkonsulantarpoli;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;

class DaftarrajalController extends Controller
{

    public static function simpanMpasien($request)
    {

        $gelardepan = '';
        $gelarbelakang = '';
        $nik = '';
        $nomoridentitaslain = '';
        $noteleponrumah = '';
        $nokabpjs = '';
        if ($request->gelardepan === '' || $request->gelardepan === null) {
            $gelardepan = '';
        } else {
            $gelardepan = $request->gelardepan;
        }

        if ($request->gelarbelakang === '' || $request->gelarbelakang === null) {
            $gelarbelakang = '';
        } else {
            $gelarbelakang = $request->gelarbelakang;
        }

        if ($request->nik === '' || $request->nik === null) {
            $nik = '';
            $nomoridentitaslain = $request->nomoridentitaslain;
        } else {
            $nik = $request->nik;
            $nomoridentitaslain = '';
        }

        if ($request->noteleponrumah === '' || $request->noteleponrumah === null) {
            $noteleponrumah = '';
        } else {
            $noteleponrumah = $request->noteleponrumah;
        }

        if ($request->nokabpjs === '' || $request->nokabpjs === null) {
            $nokabpjs = '';
        } else {
            $nokabpjs = $request->nokabpjs;
        }


        $request->validate([
            'norm' => 'required|string|max:6|min:6',
            'tglmasuk' => 'required|date_format:Y-m-d H:i:s',
            'tgllahir' => 'required|date_format:Y-m-d'
        ]);
        $masterpasien = Mpasien::updateOrCreate(
            ['rs1' => $request->norm],
            [
                'rs2' => $request->nama,
                'rs3' => $request->sapaan,
                'rs4' => $request->alamat,
                'alamatdomisili' => $request->alamatdomisili,
                'rs5' => $request->kelurahan,
                'kd_kel' => $request->kodekelurahan,
                'rs6' => $request->kecamatan,
                'kd_kec' => $request->kodekecamatan,
                'rs7' => $request->rt,
                'rs8' => $request->rw,
                'rs10' => $request->propinsi,
                'kd_propinsi' => $request->kodepropinsi,
                'rs11' => $request->kabupatenkota,
                'kd_kota' => $request->kodekabupatenkota,
                'rs49' => $nik,
                'rs37' => $request->templahir,
                'rs16' => $request->tgllahir,
                'rs17' => $request->kelamin,
                'rs19' => $request->pendidikan,
                'kd_kelamin' => $request->kodekelamin,
                'rs22' => $request->agama,
                'kd_agama' => $request->kodemapagama,
                'rs39' => $request->suku,
                'rs55' => $request->noteleponhp,
                'bahasa' => $request->bahasa,
                'noidentitaslain' => $nomoridentitaslain,
                'namaibu' => $request->namaibukandung,
                'kodepos' => $request->kodepos,
                'kd_negara' => $request->negara,
                'kd_rt_dom' => $request->rtdomisili,
                'kd_rw_dom' => $request->rwdomisili,
                'kd_kel_dom' => $request->kodekelurahandomisili,
                'kd_kec_dom' => $request->kodekecamatandomisili,
                'kd_kota_dom' => $request->kodekabupatenkotadomisili,
                'kodeposdom' => $request->kodeposdomisili,
                'kd_prov_dom' => $request->kodepropinsidomisili,
                'kd_negara_dom' => $request->negaradomisili,
                'noteleponrumah' => $noteleponrumah,
                'kd_pendidikan' => $request->kodependidikan,
                'kd_pekerjaan' => $request->pekerjaan,
                'flag_pernikahan' => $request->statuspernikahan,
                'rs46' => $nokabpjs,
                'rs40' => $request->barulama,
                'gelardepan' => $gelardepan,
                'gelarbelakang' => $gelarbelakang,
                'bacatulis' => $request->bacatulis,
                'kdhambatan' => $request->kdhambatan
            ]
        );
        return $masterpasien;
    }

    public static function simpankunjunganpoli(Request $request)
    {
        if ($request->barulama === 'baru') {
            $data = Mpasien::where('rs1', $request->norm)->first();
            if ($data) {
                return new JsonResponse([
                    'message' => 'Nomor RM Sudah ada',
                    'data' => $data
                ], 410);
            }
            $data2 = Mpasien::where('rs49', $request->nik)->first();
            if ($data2) {
                return new JsonResponse([
                    'message' => 'NIK Sudah ada',
                    'data' => $data
                ], 410);
            }
        }
        $masterpasien = self::simpanMpasien($request);
        if (!$masterpasien) {
            return new JsonResponse(['message' => 'DATA MASTER PASIEN GAGAL DISIMPAN/DIUPDATE'], 500);
        }
        $tglmasukx = Carbon::create($request->tglmasuk);
        $tglmasuk = $tglmasukx->toDateString();
        $cekpoli = KunjunganPoli::where('rs2', $request->norm)
            ->where('rs8', $request->kodepoli)
            ->whereDate('rs3', $tglmasuk)
            ->count();

        if ($cekpoli > 0) {
            return new JsonResponse(['message' => 'PASIEN SUDAH ADA DI HARI DAN POLI YANG SAMA'], 500);
        }

        DB::select('call reg_rajal(@nomor)');
        $hcounter = DB::table('rs1')->select('rs13')->get();
        $wew = $hcounter[0]->rs13;
        $noreg = FormatingHelper::gennoreg($wew, 'J');

        $input = new Request([
            'noreg' => $noreg
        ]);

        $input->validate([
            'noreg' => 'required|unique:rs17,rs1'
        ]);


        // 09-Mei-2024 aku hari ganti atau menambahkan kode ini disuruh otomatiskan DPJP sama keputusan rapat (jare mbak septi)
        // cr dulu di kepegx berdasarkan kddpjp
        // setelah ktm .... ambil kdpegsimrs
        // gk ktmu '' (string kosong)


        $dpjpDariPendaftaran = $request->dpjp;

        $pegawai = Pegawai::where('kddpjp', $dpjpDariPendaftaran)
            ->where('aktif', '=', 'AKTIF')->first();

        $kdPegSimrs = '';
        if ($pegawai) {
            $kdPegSimrs = $pegawai->kdpegsimrs ?? '';
        }



        $simpankunjunganpoli = KunjunganPoli::create([
            'rs1' => $input->noreg,
            'rs2' => $request->norm,
            'rs3' => $request->tglmasuk,
            'rs6' => $request->asalrujukan,
            'rs8' => $request->kodepoli,
            //'rs9' => $request->dpjp,
            'rs9' => $kdPegSimrs,
            'rs10' => $dpjpDariPendaftaran,
            'rs10' => 0,
            'rs11' => '',
            'rs12' => 0,
            'rs13' => 0,
            'rs14' => $request->kodesistembayar,
            'rs15' => $request->karcis,
            'rs18' => auth()->user()->pegawai_id,
            'rs20' => 'Pendaftaran',

        ]);
        if (!$simpankunjunganpoli) {
            return new JsonResponse(['message' => 'kunjungan tidak tersimpan'], 500);
        }


        $kode_biaya =  $request->kode_biaya;
        $nama_biaya =  $request->nama_biaya;
        $sarana =  $request->sarana;
        $pelayanan =  $request->pelayanan;

        // foreach ($kode_biaya as $key => $value) {

        $kar = Karcispoli::firstOrCreate(
            [
                'rs2' => $request->norm,
                'rs4' => $request->tglmasuk,
                'rs3' => $kode_biaya,
            ],
            [
                'rs1' => $input->noreg,
                // 'rs3' => $xxx->kode_biaya,
                'rs5' => 'D',
                'rs6' => $nama_biaya,
                'rs7' => $sarana,
                'rs8' => $request->kodesistembayar,
                'rs10' => auth()->user()->pegawai_id,
                // 'rs11' => $xxx->pelayanan,
                'rs11' => $pelayanan,
                'rs12' => auth()->user()->pegawai_id,
                'rs13' => '1'
            ]
        );

        //     array_push($anu, $kar);
        // }

        if (!$kar) {
            $hapuskunjunganpoli = KunjunganPoli::where('rs1', $input->noreg)->first()->delete();
            return new JsonResponse(['message' => 'karcis gagal disimpan'], 500);
        }

        // simpan jika ada jawaban konsul Poli jika Ada
        if ($request->has('rs141_id')) {
            $head = [
                'rs141_id' => $request->rs141_id,
                'noreg' => $input->noreg
            ];
            $updatejawabanKonsul = PlaningController::updateNoregJawabanKonsul($head);
        }
        //------------LOG ANTRIAN----------------//
        // $updatelogantrian = self::updatelogantrian($request,$input);
        $tgl = Carbon::now()->format('Y-m-d');
        $noantrian = $request->noantrian;
        if ($request->noantrian === '') {
            return new JsonResponse(['message' => 'tidak ada no antrian'], 500);
        }
        $updatelogantrian = Logantrian::where('nomor', '=', $noantrian)
            ->whereDate('tgl', '=', $tgl)->first();

        // return new JsonResponse([
        //     'log' => $updatelogantrian,
        //     'noantrian' => $noantrian,
        //     'req noantrian' => $request->noantrian,
        // ]);

        // if (!$updatelogantrian) {
        //     $hapuskunjunganpoli = KunjunganPoli::where('rs1', $input->noreg)->first()->delete();
        //     $hapuskarcis = Karcispoli::where('rs1', $input->noreg)->first()->delete();
        //     return new JsonResponse(['message' => 'gagal UPDATE LOG ANTIRAN'], 500);
        // }
        if ($updatelogantrian) {
            $updatelogantrian->update(['noreg' => $input->noreg, 'norm' => $request->norm]);
        }

        //------------BPJS ANTRIAN----------------//
        //$bpjs_antrian = self::bpjs_antrian($request,$input);
        $tgl = Carbon::now()->format('Y-m-d');
        $noantrian = $request->noantrian;


        //  PASIEN MJKN ======================================================================================
        $bpjsantrian = Bpjsantrian::select('id', 'nomorantrean')
            ->where('nomorantrean', '=', $noantrian)
            ->when($request->nokabpjs, function ($q) use ($request) {
                $q->where('nomorkartu', $request->nokabpjs);
            })
            ->whereDate('tanggalperiksa', '=', $tgl)->first();
        if (!$bpjsantrian) {

            // $hapuskunjunganpoli = KunjunganPoli::where('rs1' , $input->noreg)->first()->delete();
            // $hapuskarcis = Karcispoli::where('rs1', $input->noreg)->first()->delete();
            // return new JsonResponse(['message' => 'DATA PADA BPJS ANTRIAN TIDAK DITEMUKAN'],500);
            BridantrianbpjsController::addantriantobpjs($input->noreg, $request);
            BridantrianbpjsController::updateMulaiWaktuTungguAdmisi($request, $input);
            BridantrianbpjsController::updateAkhirWaktuTungguAdmisi($input);
            // BridantrianbpjsController::updateWaktu($input, 3);
            $cetakantrian = AntrianController::ambilnoantrian($request, $input);
            return new JsonResponse([
                'message' => 'data berhasil disimpan',
                'antrian' => $cetakantrian,
                'noreg' => $input->noreg
            ], 200);
        }

        $id = $bpjsantrian->id;
        $nomorantrean = $bpjsantrian->nomorantrean;
        $updatebpjsantrian = Bpjsantrian::where('id', '=', $id)->first();
        $updatebpjsantrian->update([
            'noreg' => $input->noreg,
            'checkin' => date('Y-m-d H:i:s')
        ]);


        if ($request->barulama === 'baru') {
            BridantrianbpjsController::updateMulaiWaktuTungguAdmisi($request, $input);
            BridantrianbpjsController::updateAkhirWaktuTungguAdmisi($input);
            BridantrianbpjsController::updateWaktu($input, 3);
            $cetakantrian = AntrianController::ambilnoantrian($request, $input);
            return new JsonResponse([
                'message' => 'data berhasil disimpan',
                'antrian' => $cetakantrian,
                'noreg' => $input->noreg
            ], 200);
        } else {
            if ($bpjsantrian) {
                $cetakantrian = Antrianambil::create(
                    [
                        'noreg' => $input->noreg,
                        'norm' => $request->norm,
                        'tgl_booking' => date('Y-m-d'),
                        'pelayanan_id' => $request->kodepoli,
                        'nomor' => $noantrian,
                        'user_id' => auth()->user()->pegawai_id
                    ]
                );
            } else {
                $cetakantrian = AntrianController::ambilnoantrian($request, $input);
            }
            // ini juga harus di tambah task id 1 dan 2 yang dari mjkn
            BridantrianbpjsController::updateWaktu($input, 3);
            return new JsonResponse([
                'message' => 'data berhasil disimpan',
                'antrian' => $cetakantrian,
                'noreg' => $input->noreg
            ], 200);
        }
    }



    public function updatelogantrian($request, $input)
    {
        $tgl = Carbon::now()->format('Y-m-d');
        $noantrian = $request->noantrian;
        if ($request->noantrian !== '') {
            $updatelogantrian = Logantrian::where('nomor', '=', $noantrian)->whereDate('tgl', '=', $tgl)->first()
                ->update(['noreg' => $input->noreg, 'norm' => $request->norm]);
            if (!$updatelogantrian) {
                $hapuskunjunganpoli = KunjunganPoli::where('rs1', $input->noreg)->first()->delete();
                $hapuskarcis = Karcispoli::where('rs1', $input->noreg)->first()->delete();
                return new JsonResponse(['message' => 'gagal UPDATE LOG ANTIRAN']);
            }
            return $updatelogantrian;
        }
        return new JsonResponse(['message' => 'tidak ada no antrian']);
    }

    public static function bpjs_antrian($request, $input)
    {
        $tgl = Carbon::now()->format('Y-m-d');
        $noantrian = $request->noantrian;
        $bpjsantrian = Bpjsantrian::where('nomorantrean', '=', $noantrian)->whereDate('tanggalperiksa', '=', $tgl)->first()
            ->update(['noreg' => $input->noreg]);
        if ($bpjsantrian) {
            if ($request->barulama === 'baru') {
                // updateMulaiWaktuTungguAdmisi($noregx,$no_antrian); ------------------>>iki sek drong
                $updateMulaiWaktuTungguAdmisi = BridantrianbpjsController::updateMulaiWaktuTungguAdmisi($request, $input);
                // updateAkhirWaktuTungguAdmisi($noregx); ------------------>>iki sek drong
                $bpjsantrian->update([
                    'checkin' => date('Y-m-d H:i:s')
                ]);
                //updateWaktu($noregx,3); ------------------>>iki sek drong yoo

                return $bpjsantrian;
            } else {
                $antrianambil = Antrianambil::create(
                    [
                        'noreg' => $input->noreg,
                        'norm' => $request->norm,
                        'tgl_booking' => date('Y-m-d'),
                        'pelayanan_id' => $request->kodepoli,
                        'nomor' => $noantrian,
                        'user_id' => auth()->user()->pegawai_id
                    ]
                );
                return $antrianambil;
            }
        } else {
            // tambahAntrian($noregx,[ -------------------------------->>>sek dorong
            //     'kodedpjp'=>$_POST['kodedpjp'],
            //     'dpjp'=>$_POST['dpjp'],
            //     'no_antrian'=>$no_antrian
            // ]);
            // updateMulaiWaktuTungguAdmisi($noregx,$no_antrian);
            // updateAkhirWaktuTungguAdmisi($noregx);
        }
    }

    public function simpandaftar(Request $request)
    {
        // try {
        //code...
        // DB::beginTransaction();

        //-----------Masuk Transaksi--------------
        // $user = auth()->user(]);
        $masterpasien = $this->simpanMpasien($request);
        $simpankunjunganpoli = $this->simpankunjunganpoli($request);
        if (!$simpankunjunganpoli) {
            return new JsonResponse(['msg' => 'kunjungan poli tidak tersimpan']);
        }
        return ($simpankunjunganpoli);
        $karcis = $this->simpankarcis($request, $simpankunjunganpoli['input']->noreg);


        $updateantrian = $this->updatelogantrian($request, $simpankunjunganpoli['input']->noreg);
        // $bpjs_antrian = $this->bpjs_antrian($request, date('Y-m-d'), $simpankunjunganpoli['input']->noreg);
        // $addantriantobpjs = BridantrianbpjsController::addantriantobpjs($request,$simpankunjunganpoli['input']->noreg);

        // DB::commit();
        return new JsonResponse(
            [
                'message' => 'DATA TERSIMPAN...!!!',
                'noreg' => $simpankunjunganpoli ? $simpankunjunganpoli['input']->noreg : 'gagal',
                'cek' => $simpankunjunganpoli ? $simpankunjunganpoli['count'] : 'gagal',
                'masuk' => $simpankunjunganpoli ? $simpankunjunganpoli['masuk'] : 'gagal',
                'hasil' => $simpankunjunganpoli ? $simpankunjunganpoli['simpan'] : 'gagal',
                'karcis' => $karcis ? $karcis : 'gagal',
                'updateantrian' => $updateantrian ? $updateantrian : 'gagal',
                // 'bpjs_antrian' => $bpjs_antrian ? $bpjs_antrian : 'gagal',
                // 'addantriantobpjs' => $addantriantobpjs ? $addantriantobpjs : 'gagal',
                'master' => $masterpasien,
            ],
            200
        );
        // } catch (\Exception $th) {
        //throw $th;
        //     DB::rollBack();
        //     return response()->json(['Gagal tersimpan' => $th], 500);
        //  }
    }

    public function daftarkunjunganpasienbpjs()
    {
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }
        $daftarkunjunganpasienbpjs = KunjunganPoli::select(
            'rs17.rs1', // iki tak munculne maneh gawe relasi with
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs15.bahasa as bahasa',
            'rs15.bacatulis as bacatulis',
            'rs15.kdhambatan as kdhambatan',
            'rs15.rs2 as name',
            'rs222.rs8 as sep',
            'gencons.norm as generalconsent',
            'gencons.ttdpasien as ttdpasien',
            // 'bpjs_respon_time.taskid as taskid',
            // TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15 . rs16, now()), rs15 . rs16), now(), " Hari ")
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('gencons', 'gencons.norm', '=', 'rs17.rs2')
            // ->leftjoin('bpjs_respon_time', 'bpjs_respon_time.noreg', '=', 'rs17.rs1')
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs19.rs4', '=', 'Poliklinik')
            ->where('rs17.rs8', '!=', 'POL014')
            ->where(function ($q) {
                // 'rs9.rs9', '=', request('kdbayar') ?? 'BPJS'
                if (request('kdbayar') !== 'ALL') {
                    $q->where('rs9.rs9', '=', 'BPJS');
                }
            })
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->with(['taskid' => function ($q) {
                $q->orderBy('taskid', 'DESC');
            }, 'generalcons:norm,ttdpasien,ttdpetugas,hubunganpasien,pdf'])

            ->orderby('rs17.rs3', 'DESC')
            ->groupBy('rs17.rs1')
            ->paginate(request('per_page'));

        return new JsonResponse($daftarkunjunganpasienbpjs);
    }

    public function antrianmobilejkn()
    {
        if (request('tgl') === '' || request('tgl') === null) {
            $tgl = Carbon::now()->format('Y-m-d');
            $tglx = Carbon::now()->format('Y-m-d');
        } else {
            $tgl = request('tgl');
            $tglx = request('tgl');
        }
        $antrianmjkn = Bpjsantrian::select(
            'bpjs_antrian.kodebooking',
            'bpjs_antrian.nomorantrean',
            'bpjs_antrian.nomorkartu',
            'bpjs_antrian.noreg',
            'bpjs_antrian.norm',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs17 as kelamin',
            'bpjs_antrian.tanggalperiksa',
            'bpjs_antrian.namapoli',
            'bpjs_antrian.namadokter',
            'bpjs_antrian.nomorreferensi',
            'bpjs_antrian.checkin',
            'bpjs_antrian.batal',
            'bpjs_antrian.created_at',
            'rs222.rs8 as seprajal'
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'bpjs_antrian.norm')
            ->leftjoin('rs222', 'rs222.rs1', '=', 'bpjs_antrian.noreg')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('bpjs_antrian.kodebooking', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('bpjs_antrian.nomorantrean', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('bpjs_antrian.noreg', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('bpjs_antrian.norm', 'LIKE', '%' . request('q') . '%');
            })
            ->whereBetween('bpjs_antrian.tanggalperiksa', [$tgl, $tglx])
            ->paginate(request('per_page'));

        return new JsonResponse($antrianmjkn);
    }

    public function daftarkunjunganpasienumum()
    {
        if (request('tgl') === '' || request('tgl') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('tgl') . ' 00:00:00';
            $tglx = request('tgl') . ' 23:59:59';
        }
        $daftarkunjunganpasienumum = KunjunganPoli::select(
            'rs17.rs1', // iki tak munculne maneh gawe relasi with
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'generalconsent.norm as generalconsent',
            'karcislog.nokarcis as nokarcis'
            // 'bpjs_respon_time.taskid as taskid',
            // TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15 . rs16, now()), rs15 . rs16), now(), " Hari ")
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('karcislog', 'karcislog.noreg', '=', 'rs17.rs1') //sep
            ->leftjoin('generalconsent', 'generalconsent.norm', '=', 'rs17.rs2')
            // ->leftjoin('bpjs_respon_time', 'bpjs_respon_time.noreg', '=', 'rs17.rs1')
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs19.rs4', '=', 'Poliklinik')
            ->where('rs17.rs8', '!=', 'POL014')
            ->where('rs9.rs9', '!=', 'BPJS')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->with(['taskid' => function ($q) {
                $q->orderBy('taskid', 'DESC');
            }])
            ->orderby('rs17.rs3', 'DESC')
            ->paginate(request('per_page'));

        return new JsonResponse($daftarkunjunganpasienumum);
    }

    public function listkonsulantarpoli()
    {
        $listkonsulantarpoli = Listkonsulantarpoli::select(
            'listkonsulanpoli.noreg_lama as noreg_lama',
            'listkonsulanpoli.norm as norm',
            'rs15.rs2 as nama',
            'listkonsulanpoli.flag as flag',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs222.rs8 as seprajal',
            'rs17.rs3 as tglmasuk',
            'rs17.rs6 as asalrujukan',
            'rs17.rs8 as kodepoli',
            'rs17.rs14 as kodesistembayar'
        )->leftjoin('rs15', 'rs15.rs1', 'listkonsulanpoli.norm')
            ->leftjoin('rs17', 'rs17.rs1', 'listkonsulanpoli.noreg_lama')
            ->leftjoin('rs222', 'rs222.rs1', '=', 'listkonsulanpoli.noreg_lama')
            ->get();
        return new JsonResponse($listkonsulantarpoli);
    }

    public function hapuspasien(Request $request)
    {
        $cek = KunjunganPoli::where('rs1', $request->noreg)->where('rs19', '!=', '')->count();

        if ($cek > 0) {
            return new JsonResponse(['message' => 'Maaf Pasien Ini Sudah Menerima Pelayanan di Poli...!!!'], 500);
        }

        $kunj = KunjunganPoli::where('rs1', $request->noreg)->first();
        $hapuskunjunganpoli = KunjunganPoli::where('rs1', $request->noreg)->first();
        if ($hapuskunjunganpoli != null) {
            $hapuskunjunganpoli->delete();
            if (!$hapuskunjunganpoli) {
                return new JsonResponse(['message' => 'Maaf Pasien Gagal Dihapus...!!!'], 500);
            }
        }

        $hapuskarcis = Karcispoli::where('rs1', $request->noreg)->first();
        if ($hapuskarcis != null) {
            $hapuskarcis->delete();
            if (!$hapuskarcis) {
                return new JsonResponse(['message' => 'Maaf Pasien Gagal Dihapus...!!!'], 500);
            }
        }

        // hapus antrian
        $antrian = Antrianambil::where('noreg', $request->noreg)->first();
        if ($antrian) {
            $antrian->delete();
        }

        if ($request->nosep != '' || $request->nosep != null) {
            if ($kunj->rs4 === '') {
                Bridbpjscontroller::hapussep($request);
                return new JsonResponse(['message' => 'Data Berhasil Dihapus...!!!'], 200);
            } else {
                return new JsonResponse(['message' => 'Data Berhasil Dihapus...!!! dan SEP tidak dihapus'], 200);
            }
        } else {
            return new JsonResponse(['message' => 'Data Berhasil Dihapus...!!!'], 200);
        }
    }

    public function simpankonsul(Request $request)
    {
        $simpan = $simpankunjunganpoli = KunjunganPoli::create([
            'rs1' => $request->noreg,
            'rs2' => $request->norm,
            'rs3' => $request->tglmasuk,
            'rs4' => $request->noreg,
            'rs6' => $request->asalrujukan,
            'rs8' => $request->kodepoli,
            //'rs9' => $request->dpjp,
            'rs10' => 0,
            'rs11' => '',
            'rs12' => 0,
            'rs13' => 0,
            'rs14' => $request->kodesistembayar,
            'rs15' => $request->karcis,
            'rs18' => auth()->user()->pegawai_id,
            'rs20' => 'Pendaftaran',

        ]);
        if (!$simpankunjunganpoli) {
            return new JsonResponse(['message' => 'kunjungan tidak tersimpan'], 500);
        }
    }

    // ini untuk mencari rujukan keluar rs di bpjs_http_respon
    public function caruRujukanKeluarRs(Request $request)
    {

        $data = WaktupulangPoli::where('rs2', $request->norm)
            ->with([
                'masterpoli',
                'rekomdpjp' => function ($q) {
                    $q->with('poli')
                        ->orderBy('id', 'DESC');
                }
            ])
            ->orderby('tgl', 'DESC')
            ->first();
        if ($data) {
            if ($data->rs4 == 'Rumah Sakit Lain') {
                $data->load([
                    'bpjsresponse' => function ($q) {
                        $q->where('url', '/Rujukan/2.0/insert')
                            ->orWhere('url', '/Rujukan/2.0/update')
                            ->orderBy('tgl', 'DESC');
                    }
                ]);
            }
            if ($data->rs4 == 'Kontrol') {
                $data->load([
                    'kontrol' => function ($q) {
                        $q->orderBy('tglRencanaKontrol', 'DESC');
                    }
                ]);
            }
        }

        return new JsonResponse([
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
}
