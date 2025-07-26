<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\Serahterima_header;
use App\Models\Siasik\TransaksiLS\Serahterima_rinci;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\Return_;

class SerahterimaController extends Controller
{
    public function listdatastp()
    {
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = Serahterima_header::whereBetween('serahterima_heder.tgltrans', [$tahunawal.'-01-01', $tahun.'-12-31'])
        ->select('serahterima_heder.*');

        if ($sa !== 'sa' && $sa !==  '1619' && $sa !==  '38' && $sa !==  '1618') {
            $data->where('serahterima_heder.kodepptk', $pegawai);
        }
            $stp=$data->with('rinci')
            ->when(request('q'), function($q){
                $q->where('serahterima_heder.noserahterimapekerjaan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('serahterima_heder.nokontrak', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('serahterima_heder.namaperusahaan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('serahterima_heder.kegiatanblud', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('serahterima_heder.namapptk', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('serahterima_heder.totalpermintaanls', 'LIKE', '%' . request('q') . '%')
                    ;
            })
            ->orderBy('serahterima_heder.tgltrans', 'desc')
            ->get();
        return new JsonResponse($stp);

    }
    public function getkontrak(){
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $tahun=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $data = KontrakPengerjaan::where('kunci', '!=', '');
        if ($sa !== 'sa') {
            $data->where('kodepptk', $pegawai);
        }
        $getkontrak=$data->whereBetween('tgltrans', [$tahun.'-01-01', $tahun.'-12-31'])
        ->when(request('q'), function($q){
            $q->where('nokontrak', 'LIKE', '%' . request('q') . '%')
                ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
                ->orWhere('namapptk', 'LIKE', '%' . request('q') . '%')
                ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })
        ->get();
        return new JsonResponse($getkontrak);
    }
    public function savedata(Request $request)
    {
        // Tentukan noserahterima
        if (empty($request->noserahterimapekerjaan)) {
            // Panggil stored procedure noserahterimaPekerjaan di siasik
            DB::connection('siasik')->select('call noserahterimapekerjaan(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('noserahterimapekerjaan')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur noserahterimaPekerjaan');
            }
            $nomer = (int)$x->noserahterimapekerjaan; // Gunakan nomor dari counter sebagai $total

            // Format nomor menggunakan FormatingHelper::nostp
            $noserahterima = FormatingHelper::nostp($nomer, 'STP');
        } else {
            $noserahterima = $request->noserahterimapekerjaan;
        }
        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        try
        {
            DB::beginTransaction();
            $save = Serahterima_header::updateOrCreate
            (
                [
                    'noserahterimapekerjaan' => $noserahterima
                ],
                [
                    'nokontrak'=>$request->nokontrak ?? '',
                    'kodepihakketiga'=>$request->kodepihakketiga ?? '',
                    'namaperusahaan'=>$request->namaperusahaan ?? '',
                    'kodemapingrs'=>$request->kodemapingrs ?? '',
                    'namasuplier'=>$request->namasuplier ?? '',
                    'tglmulaikontrak'=>$request->tglmulaikontrak ?? '',
                    'tglakhirkontrak'=>$request->tglakhirkontrak ?? '',
                    'tgltrans'=>$request->tgltrans ?? '',
                    'kodepptk'=>$request->kodepptk ?? '',
                    'namapptk'=>$request->namapptk ?? '',
                    'program'=>$request->program ?? '',
                    'kegiatan'=>$request->kegiatan ?? '',
                    'kodekegiatanblud'=>$request->kodekegiatanblud ?? '',
                    'kegiatanblud'=>$request->kegiatanblud ?? '',
                    'tglentry'=>$time ?? '',
                    'userentry'=>$pegawai ?? '',
                ]
            );
            foreach ($request->rinci as $rinci)
            {
                $save->rinci()->create(
                    [
                        'noserahterimapekerjaan'=>$save->noserahterimapekerjaan,
                        'nokontrak'=>$rinci['nokontrak'] ?? '',
                        'koderek50'=>$rinci['koderek50'] ?? '',
                        'uraianrek50'=>$rinci['uraianrek50'] ?? '',
                        'koderek108'=>$rinci['koderek108'] ?? '',
                        'uraian108'=>$rinci['uraian108'] ?? '',
                        'itembelanja'=>$rinci['itembelanja'] ?? '',
                        'idserahterima_rinci'=>$rinci['idserahterima_rinci'] ?? '',
                        'volume'=>$rinci['volume'] ?? '',
                        'satuan'=>$rinci['satuan'] ?? '',
                        'harga'=>$rinci['harga'] ?? '',
                        'total'=>$rinci['total'] ?? '',
                        'volumels'=>$rinci['volumels'] ?? '',
                        'hargals'=>$rinci['hargals'] ?? '',
                        'totalls'=>$rinci['totalls'] ?? '',
                        'nominalpembayaran'=>$rinci['nominalpembayaran'] ?? '',
                        'tglentry'=>$time ?? '',
                        'userentry'=>$pegawai ?? '',
                    ]
                );
            }
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!',
                    'result' => $save,
                ], 200
            );
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse(
                [
                    'message' => 'Ada Kesalahan',
                    'error' => $er->getMessage()
                ], 500
            );
        }
    }

    public function getlistform()
    {
        $data = Serahterima_header::where('serahterima_heder.noserahterimapekerjaan', request('noserahterimapekerjaan'))
        ->select('serahterima_heder.noserahterimapekerjaan',
                        'serahterima_heder.tgltrans',
                        'serahterima50.*',
        )
        ->join('serahterima50', 'serahterima50.noserahterimapekerjaan', 'serahterima_heder.noserahterimapekerjaan')
        ->get();

        return new JsonResponse($data);
    }

    public function deleterinci(Request $request)
    {
        $header = Serahterima_header::where('noserahterimapekerjaan', $request->noserahterimapekerjaan)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'NPD Masih Dikunci'], 500);
        }

        if($request->id){
            $findrinci = Serahterima_rinci::where('id', $request->id)->first();
        }

        if (!$findrinci) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        else {
        $findrinci->delete();
        }

        $rinciAll = Serahterima_rinci::where('noserahterimapekerjaan', $request->noserahterimapekerjaan)->get();
        if(count($rinciAll) == 0){
            $header = Serahterima_header::where('noserahterimapekerjaan', $request->noserahterimapekerjaan)->first();
            if($header){
                $header->delete();
                return new JsonResponse(['message' => 'Data Berhasil dihapus'], 200);
            }  else {
                return new JsonResponse([
                    'message' => 'Data header tidak ditemukan',
                ], 404);
            }
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus',
             'data' => $rinciAll
        ]);
    }

    public function kuncidata(Request $request)
    {
        $request->validate([
            'noserahterimapekerjaan' => 'required|string'
        ]);

        try {
            $header = Serahterima_header::where('noserahterimapekerjaan', $request->noserahterimapekerjaan)->first();

            if (!$header) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            if ($header->kunci == '1') {
                // Buka kunci → harus superadmin
                $user = auth()->user()->pegawai_id;
                $pg = Pegawai::find($user);

                if (!$pg || $pg->kdpegsimrs !== 'sa') {
                    return response()->json(['message' => 'Anda tidak Memiliki Izin Membuka Kunci Data ini, Silahkan Hubungi Admin'], 403);
                }

                if (!empty($header->nonpdls)) {
                    return response()->json(['message' => 'Serahterima Sudah di NPD-LS'], 400);
                }

                $header->kunci = '';
                $header->save();

                return response()->json(['message' => 'Kunci berhasil dibuka'], 200);
            } else {
                $header->kunci = '1';
                $header->save();

                return response()->json(['message' => 'Data berhasil dikunci'], 200);
            }

        } catch (\Exception $e) {
            // Log::error('Gagal membuka kunci serahterima: ' . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuka kunci',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
