<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\Pasien;
use App\Models\PemeriksaanLaborat;
use App\Models\TransaksiLaborat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransaksiLaboratLuarController extends Controller
{
    public function index()
    {
        $now = date('Y-m-d');
        $to = date('2018-05-02');
        $query = LaboratLuar::query()
            ->selectRaw('nota,tgl,nama,kelamin,alamat,tgl_lahir,pengirim,perusahaan_id,lunas,akhir,akhirx, kd_lab')
            ->filter(request(['q']))
            ->with(['perusahaan', 'pemeriksaan_laborat', 'catatan'])
            ->groupBy('nota')
            ->latest('tgl');
        // ->whereDate('rs3', '=', $now);
        $data = $query->paginate(request('per_page'));
        // $count = collect($query->get())->count();
        // ->simplePaginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function get_details()
    {
        $data = LaboratLuar::query()
            ->where('nota', request('nota'))
            ->with(['perusahaan', 'pemeriksaan_laborat', 'catatan'])
            ->get();

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {

        // try {

        //     DB::beginTransaction();

        $temp = collect($request->details);
        // $data = PemeriksaanLaborat::whereIn('rs1',$temp)->get();

        $n = Carbon::now();
        $tgl = $n->toDateTimeString();

        $containers = [];

        foreach ($temp as $key) {
            LaboratLuar::create([
                'kd_lab' => $key['rs1'],
                'tarif_sarana' => $key['rs3'],
                'tarif_pelayanan' => $key['rs4'],
                'nama' => $request->nama,
                'kelamin' => $request->kelamin,
                'pengirim' => $request->pengirim,
                'tgl_lahir' => $request->tgl_lahir,
                'temp_lahir' => $request->temp_lahir,
                'nota' => $request->nota,
                'alamat' => $request->alamat,
                'jenispembayaran' => $request->jenispembayaran,
                'nosurat' => $request->nosurat ? $request->nosurat : '',
                'noktp' => $request->noktp,
                'agama' => $request->agama,
                'nohp' => $request->nohp,
                'kode_pekerjaan' => $request->kode_pekerjaan,
                'nama_pekerjaan' => $request->kode_pekerjaan,
                'sampel_diambil' => $request->sampel_diambil,
                'jam_sampel_diambil' => $request->jam_sampel_diambil,
                'tgl' => $tgl,
                'jml' => 1,
            ]);
        }
        return new JsonResponse(['message' => 'success'], 201);
    }

    public function destroy(Request $request)
    {
        $nota = $request->nota;
        $data = LaboratLuar::where('nota', $nota);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }

    public function kunci_dan_kirim_ke_lis()
    {
        $data = LaboratLuar::query()
            ->where('nota', request('nota'))
            ->with(['perusahaan', 'pemeriksaan_laborat', 'catatan'])
            ->get();

        if (!$data) {
            return response()->json([
                'message' => 'Error! Server tidak menaggapi'
            ], 500);
        }
        return $this->kirim_ke_lis($data);
    }

    public function kirim_ke_lis($data)
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);

        $kodes = collect($data)->pluck('kd_lab');
        $kode_lab = implode('~', $kodes->toArray());

        // $apiURL = 'http://172.16.24.2:83/prolims/api/lis/postOrder';
        $apiURL = 'http://192.168.101.200:83/prolims/api/lis/postOrder';
        $postInput = [
            "ADDRESS" => $data[0]->alamat,
            "BOD" => $data[0]->tgl_lahir,
            "CLASS" => "",
            "CLASS_NAME" => "-",
            "COMPANY" => "-",
            "COMPANY_NAME" => "RSUD MOCH SALEH",
            "DATE_ORDER" => date('Ymdhis', strtotime($data[0]->tgl)),
            // "DATE_ORDER"=> time(),
            "DIAGNOSA" => "-",
            "DOCTOR" => "17",
            "DOCTOR_NAME" => $data[0]->pengirim,
            "GLOBAL_COMMENT" => "laborat-luar",
            "IDENTITY_N" => "-",
            "IS_CITO" => "0",
            "KODE_PRODUCT" => $kode_lab,
            "ONO" => $data[0]->nota,
            "PATIENT_NAME" => $data[0]->nama,
            "EMAIL" => "rsudmochsaleh@app.com",
            "PATIENT_NO" => time(),
            "ROOM" => "",
            "ROOM_NAME" => "",
            "SEX" => $data[0]->kelamin === "Laki-laki" ? "1" : "2",
            "STATUS" => "N",
            "TYPE_PATIENT" => "1"
        ];

        $headers = [
            'X-id' => $xid,
            'X-timestamp' => $xtimestamp,
            'X-signature' => $xsignature,
        ];

        $response = Http::withHeaders($headers)->post($apiURL, $postInput);
        if (!$response) {
            return response()->json([
                'message' => 'Harap Ulangi... LIS ERROR'
            ], 500);
        }

        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);

        LaboratLuar::where('nota', $data[0]->nota)->update(['akhir' => "1"]);

        return response()->json(['responseNya' => $responseBody, 'dataku_mbalik' => $postInput]);

        // return response()->json($postInput);
    }
}
