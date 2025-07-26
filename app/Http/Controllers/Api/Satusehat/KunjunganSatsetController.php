<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\BridgingSatsetHelper;
use App\Helpers\PostKunjunganHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Pegawai\Extra;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isEmpty;

class KunjunganSatsetController extends Controller
{
    public function listKunjungan()
    {
        $jenis_kunjungan = request('jenis');
        if ($jenis_kunjungan === 'rajal') {
            return self::rajal(request()->all());
        }
    }

    public static function rajal($req)
    {
        // return $req;
        if (!isset($req['to'])) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = $req['to'] . ' 00:00:00';
            $tglx = $req['from'] . ' 23:59:59';
        }

        $data = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs8',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs15.rs2 as nama',
            'rs15.rs49 as nik',
            'rs15.satset_uuid as pasien_uuid',
            'rs17.rs19 as status',
            'satsets.uuid as satset',
            'satset_error_respon.uuid as satset_error',
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('satsets', 'satsets.uuid', '=', 'rs17.rs1') //satset
            ->leftjoin('satset_error_respon', 'satset_error_respon.uuid', '=', 'rs17.rs1') //satset error

            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            // ->where('rs17.rs8', $user->kdruangansim ?? '')
            ->where('rs17.rs8', '!=', 'POL014')
            ->where('rs17.rs19', '=', '1')

            ->where('rs19.rs5', '=', '1')
            ->where('rs19.rs4', '=', 'Poliklinik')
            ->whereNull('satsets.uuid')

            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%') //pasien nama
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%') //pasien
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })

            ->with([
                'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,satset_uuid',
                'relmpoli.ruang',
                //   // 1 (mulai waktu tunggu admisi),
                //   // 2 (akhir waktu tunggu admisi/mulai waktu layan admisi),
                //   // 3 (akhir waktu layan admisi/mulai waktu tunggu poli),
                //   // 4 (akhir waktu tunggu poli/mulai waktu layan poli),
                //   // 5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
                //   // 6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
                //   // 7 (akhir waktu obat selesai dibuat),
                //   // 99 (tidak hadir/batal)
                'taskid' => function ($q) {
                    $q->select('noreg', 'kodebooking', 'taskid', 'waktu', 'created_at')
                        ->orderBy('taskid', 'ASC');
                },
                'diagnosa' => function ($d) {
                    $d->with('masterdiagnosa');
                },
                'tindakan' => function ($t) {
                    $t->with('mastertindakan:rs1,rs2,mastertindakan.snowmedx', 'pegawai:nama,kdpegsimrs,satset_uuid')
                        ->orderBy('id', 'DESC');
                },
            ])

            ->orderby('rs17.rs3', 'ASC')
            ->paginate($req['per_page']);

        return response()->json($data);
    }

    public function getPasienByNikSatset(Request $request)
    {
        // return $request->all();
        $nik = $request->nik;
        $token = $request->token;
        $norm = $request->norm;
        if (!$nik) {
            return response()->json([
                'message' => 'Maaf ... Nik Pasien Tidak Ada di Database RS'
            ], 500);
        }

        // get data ke satset
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        }
        return $send;
    }

    public function kirimKunjungan(Request $request)
    {
        if (!$request->datasimpeg) {
            return response()->json([
                'message' => 'Maaf ... Dokter Ini Belum Ada di Kepegawaian RS'
            ], 500);
        }

        $practitioner = $request->datasimpeg['satset_uuid'];
        if (!$practitioner) {
            return response()->json([
                'message' => 'Maaf ... Dokter Ini Belum Terkoneksi Ke Satu Sehat'
            ], 500);
        }
        $patient = $request->pasien_uuid;
        if (!$patient) {
            return response()->json([
                'message' => 'Maaf ... Pasien Ini Belum Terkoneksi Ke Satu Sehat'
            ], 500);
        }
        $poli = $request->relmpoli;
        if (!$poli) {
            return response()->json([
                'message' => 'Maaf ... DATA POLI Belum Ada'
            ], 500);
        }

        $mappingruang = $poli['ruang'];
        if (!$mappingruang) {
            return response()->json([
                'message' => 'Maaf ... Tidak Ada Mapping Ruangan di Poli'
            ], 500);
        }
        $diagnosa = $request->diagnosa;
        if (!$diagnosa) {
            return response()->json([
                'message' => 'Maaf ... Tidak Ada Diagnosa Pada Kunjungan Ini'
            ], 500);
        }
        // return $practitioner;
        $form = PostKunjunganHelper::form($request);

        $send = BridgingSatsetHelper::post_bundle($request->token, $form, $request->noreg);
        return $send;
    }
}
