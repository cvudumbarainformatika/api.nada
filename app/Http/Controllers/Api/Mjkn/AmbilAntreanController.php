<?php

namespace App\Http\Controllers\Api\Mjkn;

use App\Helpers\AuthjknHelper;
use App\Helpers\BookingHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Dokter;
use App\Models\Antrean\Unit;
use App\Models\Pasien;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Bpjs\BpjsPasienBaru;
use App\Models\Simrs\Bpjs\Bpjsrefpoli;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AmbilAntreanController extends Controller
{
    public function byLayanan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomorkartu' => 'required',
            'nik' => 'required',
            'kodepoli' => 'required',
            'tanggalperiksa' => 'required',
            'kodedokter' => 'required',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required',
            'nomorreferensi' => 'required'
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'message' => $validator->errors()->first(),
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }


        $noBpjs = $request->input('nomorkartu');
        $noKtp = $request->input('nik');
        $kdPoli = $request->input('kodepoli');
        $tanggalperiksa = $request->input('tanggalperiksa');
        $kodedokter = $request->input('kodedokter');


        // CARI POLI
        $caripoli = Bpjsrefpoli::getByKdSubspesialis($kdPoli)->get();

        if (count($caripoli) === 0) {
            return response()->json([
                'metadata' => [
                    'message' => 'Poli tidak ditemukan',
                    'code' => 201,
                ]
            ], 201);
        }

        $poli = $caripoli[0];
        $namapoli = $poli->nmsubspesialis;

        $AntrianUnit = Unit::where('layanan_id', $poli->kdpolirs)->first();

        if (!$AntrianUnit) {
            return response()->json([
                'metadata' => [
                    'message' => 'Unit Belum Ada',
                    'code' => 201,
                ]
            ], 201);
        }

        if ($AntrianUnit->tersedia == 'Tidak Ada')
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, antrian online tidak tersedia pada poli tujuan. Silahkan untuk melakukan antrian offline.',
                    'code' => 201,
                ]
            ], 201);


        $jadwalPoli = self::cari_dokter($kdPoli, $tanggalperiksa); // json_decode($jadwalPoli) for back to jSon
        // return new JsonResponse($jadwalPoli);
        $code = $jadwalPoli['metadata']['code'];
        if ($code != 200)
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, jadwal poli tujuan tidak ditemukan pada tanggal tersebut.',
                    'code' => 201,
                ]
            ], 201);

        $cekDokter = collect($jadwalPoli['result'])->firstWhere('kodedokter', $kodedokter);



        if (!$cekDokter)
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, jadwal dokter tujuan tidak ditemukan pada tanggal tersebut.',
                    'code' => 201,
                ]
            ], 201);




        $jamTutup = strtotime($tanggalperiksa . ' 10:59:59');
        $jamSekarang = strtotime(date('Y-m-d H:i:s'));
        $day = new Carbon();
        $hrIni = $day->toDateString();

        if ($tanggalperiksa === $hrIni && $jamSekarang > $jamTutup) {
            // if($tanggalperiksa == DateController::getDate()){
            $response = [
                'metadata' => [
                    'message' => 'Maaf antrian hari ini sudah tutup jam 11:00.',
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }


        $maksimalHari = DateHelper::selisihHari($hrIni, $tanggalperiksa);
        if ($maksimalHari > 2) {
            $response = [
                'metadata' => [
                    'message' => 'Maaf antrian hanya bisa diambil maksimal 2 hari sebelum tanggal kunjungan.',
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }


        // CARI PASIEN DI SIMRS

        $pasienGetByNoBpjs = Pasien::getByNoBpjs($noBpjs)->get();
        $pasienGetByNoKtp = Pasien::getByNik($noKtp)->get();
        $bpjsPasienGetByNoBpjs = BpjsPasienBaru::getByNoBpjs($noBpjs)->get();

        $layanan_id = $poli->kdpolirs;
        $keterangan = 'Harap Hadir 30 Menit lebih Awal untuk verifikasi Pasien';
        $norm = '-';
        $pasienbaru = 0;
        $namapasien = '';
        $nohp = '';

        // CARI PASIEN DI WS BPJS
        // $cekBpjs = self::cari_dokter($noBpjs, $tanggalperiksa);


        if (count($pasienGetByNoBpjs) == 0) {
            $pasienbaru = 1;
            $response = [
                'metadata' => [
                    'message' => 'Data pasien ini tidak ditemukan, silahkan Melakukan Registrasi Pasien Baru',
                    'code' => 202,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }

        // Masih Pasien Baru
        if (count($bpjsPasienGetByNoBpjs) > 0 && count($pasienGetByNoBpjs) == 0) {
            $pasienbaru = 1;
            $layanan_id = '2';
            $norm = '';

            $tglLahir = $bpjsPasienGetByNoBpjs[0]->tanggallahir;
            $usia = DateHelper::usia($tglLahir);
            $namapasien = $bpjsPasienGetByNoBpjs[0]->nama;
            $nohp = $bpjsPasienGetByNoBpjs[0]->nohp;
            if ((int) $usia > 60) {
                $layanan_id = '3'; // LANSIA
                $keterangan = 'Silahkan peserta menunggu panggilan antrian di pendaftaran.';
            }
        } else {
            $layanan_id = $poli->kdpolirs;
            $norm = $pasienGetByNoBpjs[0]->rs1;
            $namapasien = $pasienGetByNoBpjs[0]->rs2;
            $nohp = $pasienGetByNoBpjs[0]->rs55;
            $keterangan = 'Silahkan peserta langsung datang ke pendaftaran tanpa menunggu panggilan antrian.';
        }



        // AMBIL NO ANTRIAN

        $booking = Booking::select('tanggalperiksa', 'nomorkartu')->whereBetween(
            'tanggalperiksa',
            [$tanggalperiksa . ' 00:00:00', $tanggalperiksa . ' 23:59:59']
        )
            ->where('nomorkartu', $noBpjs)
            ->count();

        if ($booking > 0) {
            $response = [
                'metadata' => [
                    'message' => 'Anda sudah mengambil antrian.. !',
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }

        $kodebooking = BookingHelper::kodeBooking($pasienbaru);
        $pasienjkn = true;
        $pasienbaru = $pasienbaru === 1 ||  $pasienbaru === '1';
        $kodepoli = $layanan_id;

        $layanan = BookingHelper::cari_layanan($pasienjkn, $pasienbaru, $kodepoli);

        if (!$layanan) {
            $msg = 'Maaf Layanan ini Belum Ada di RSUD MOHAMAD SALEH';
            $metadata = ['code' => 201, 'message' => $msg];
            $res['metadata'] = $metadata;
            return response()->json($res);
        }


        $id_layanan = $layanan->id_layanan;
        $kodelayanan = $layanan->kode;
        $kuotajkn = $layanan->kuotajkn;
        $kuotanonjkn = $layanan->kuotanonjkn;


        $cekKuota = BookingHelper::jumlahKuotaTerpesan($tanggalperiksa, $id_layanan);
        $angkaantrean = $cekKuota['angkaantrean'];
        $logJkn = $cekKuota['jkn'];
        $logNonJkn = $cekKuota['nonjkn'];

        $nomorantrean = $kodelayanan . sprintf("%03s", $angkaantrean);

        $sisakuotajkn = $kuotajkn - $logJkn;
        $sisakuotanonjkn = $kuotanonjkn - $logNonJkn;

        $os = array("1", "2", "3", "AP0001");
        if (!in_array($id_layanan, $os)) {
            if ($pasienjkn) {
                $sisakuotajkn = (int)$sisakuotajkn > 0 ? (int)$sisakuotajkn - 1 : $sisakuotajkn;
            } else {
                $sisakuotanonjkn = (int)$sisakuotanonjkn > 0 ? (int)$sisakuotanonjkn - 1 : $sisakuotanonjkn;
            }
        }

        // CEK PENUH
        $os = array("1", "2", "3", "AP0001");
        if (!in_array($id_layanan, $os)) {
            if ($pasienjkn) {
                // $sisakuotajkn = (int)$kuotajkn > 0 ? (int)$kuotajkn - (int)$angkaantrean : 0;

                if ($sisakuotajkn === 0) {
                    $msg = 'Maaf, Antrian Sudah Penuh';
                    $metadata = ['code' => 201, 'message' => $msg];
                    $res['metadata'] = $metadata;
                    return response()->json($res, 201);
                }
            } else {
                // $sisakuotanonjkn = (int)$kuotanonjkn > 0 ? (int)$kuotanonjkn - (int)$angkaantrean : 0;
                if ($sisakuotanonjkn === 0) {
                    $msg = 'Maaf, Antrian Sudah Penuh';
                    $metadata = ['code' => 201, 'message' => $msg];
                    $res['metadata'] = $metadata;
                    return response()->json($res, 201);
                }
            }
        }

        $date = Carbon::parse($tanggalperiksa);
        $dt = $date->addMinutes(10);
        $estimasidilayani = $dt->getPreciseTimestamp(3);

        $dok = $cekDokter ?? false;
        // return new JsonResponse($dok);

        $dokter = $dok ? Dokter::firstOrCreate(
            ['kodedokter' => $dok['kodedokter']],
            [
                'namadokter' => $dok['namadokter'],
                'kodesubspesialis' => $dok['kodesubspesialis'],
            ]
        ) : false;

        $saiki = new Carbon();
        $hariIni = $saiki->toDayDateTimeString();

        $save = Booking::create(
            [
                'kodebooking' => $kodebooking,
                'jenispasien' => $pasienjkn ? 'JKN' : 'NON JKN',
                'norm' => $norm,
                'namapasien' => $namapasien,
                'nomorkartu' => $noBpjs,
                'nik' => $noKtp,
                'nohp' => $nohp,
                'kodepoli' => $kdPoli,
                'namapoli' => $namapoli,
                'pasienbaru' => $pasienbaru ? 1 : 0,
                'layanan_id' => $id_layanan,
                'jeniskunjungan' => $request->jeniskunjungan,
                'dokter_id' => $dok ? $dokter->id : null,
                'tanggalperiksa' => $tanggalperiksa,
                'tgl_ambil' => $hariIni,
                'nomorreferensi' => $request->nomorreferensi,
                'nomorantrean' => null, // diisi otomatis by procedure
                'angkaantrean' => null, // diisi otomatis by procedure
                'estimasidilayani' => $estimasidilayani,
                'sisakuotajkn' => $sisakuotajkn,
                'kuotajkn' => $kuotajkn,
                'sisakuotanonjkn' => $sisakuotanonjkn,
                'kuotanonjkn' => $kuotanonjkn,
                'statuscetak' => 1,
                'keterangan' => $keterangan,
            ]
        );

        if (!$save) {
            $response = [
                'metadata' => [
                    'message' => 'Maaf Ada Kesalahan coba ulangi!',
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }

        // $sisakuotajkn = $pasienjkn ? $kuotajkn - 1 : $save->sisakuotajkn;
        // $sisakuotanonjkn = !$pasienjkn ? $kuotajkn - 1 : $save->sisakuotanonjkn;

        // Booking::find($save->id)->update(
        //     [
        //         'sisakuotajkn' => $sisakuotajkn,
        //         'sisakuotanonjkn' => $sisakuotanonjkn
        //     ]
        // );

        $look = Booking::select('nomorantrean', 'angkaantrean')->where('id', $save->id)->first();

        $response = [
            'response' => [
                'nomorantrean' => $look->nomorantrean,
                'angkaantrean' => $look->angkaantrean,
                'kodebooking' => $save->kodebooking,
                'norm' => $save->norm,
                'namapoli' => $save->namapoli,
                'namadokter' => $save->namadokter,
                'estimasidilayani' => $save->estimasidilayani,
                'sisakuotajkn' => $save->sisakuotajkn,
                'kuotajkn' => $save->kuotajkn,
                'sisakuotanonjkn' => $save->sisakuotanonjkn,
                'kuotanonjkn' => $save->kuotanonjkn,
                'keterangan' => $save->keterangan
            ],
            'metadata' => [
                'message' => 'Ok',
                'code' => 200,
            ]
        ];
        return response()->json($response, $response['metadata']['code']);
    }

    public function cari_dokter($kodepoli, $tanggal)
    {
        return BridgingbpjsHelper::get_url('antrean', 'jadwaldokter/kodepoli/' . $kodepoli . "/tanggal/" . $tanggal);
    }
    public function cari_pasien($noka, $tglSekarang)
    {
        return BridgingbpjsHelper::get_url('vclaim', 'Peserta/nokartu/' . $noka . "/tglSEP/" . $tglSekarang);
    }
}
