<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\IndikasiObat;
use App\Models\Simrs\Penunjang\Farmasinew\Mapingkelasterapi;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

class ObatnewController extends Controller
{
    public function simpan(Request $request)
    {
        if (!$request->kd_obat) {
            DB::connection('farmasi')->select('call master_obat(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('mobat')->get();
            $wew = $x[0]->mobat;
            $kodeobat = FormatingHelper::mobat($wew, 'FAR');
        } else {
            $kodeobat = $request->kd_obat;
        }
        $request['kelasterapis'] = $request->kelasterapis ?? '';
        $request['gudang'] = $request->gudang ?? '';

        $request['status_generik'] = $request->status_generik ?? '';
        $request['status_forkid'] = $request->status_forkid ?? '';
        $request['status_fornas'] = $request->status_fornas ?? '';
        $request['status_kronis'] = $request->status_kronis ?? '';
        $request['status_prb'] = $request->status_prb ?? '';
        $request['status_konsinyasi'] = $request->status_konsinyasi ?? '';
        $request['kelompok_psikotropika'] = $request->kelompok_psikotropika ?? '';
        $request['kekuatan_dosis'] = $request->kekuatan_dosis ?? '';
        $request['obat_program'] = $request->obat_program ?? '';
        $request['obat_donasi'] = $request->obat_donasi ?? '';
        $request['obat_kebijakan'] = $request->obat_kebijakan ?? '';

        $simpan = Mobatnew::updateOrCreate(
            ['kd_obat' => $kodeobat],

            $request->all()
            // 'nama_obat' => $request->nama_obat,
            // 'merk' => $request->merk,
            // 'kandungan' => $request->kandungan,
            // 'jenis_perbekalan' => $request->jenis_perbekalan,
            // 'bentuk_sediaan' => $request->bentuk_sediaan,
            // 'kode108' => $request->kode108,
            // 'uraian108' => $request->uraian108,
            // 'kode50' => $request->kode50,
            // 'uraian50' => $request->uraian50,
            // 'satuan_b' => $request->satuan_b,
            // 'satuan_k' => $request->satuan_k,
            // 'kelompok_psikotropika' => $request->kelompok_psikotropika,
            // 'kelompok_penyimpanan' => $request->kelompok_penyimpanan,
            // 'kelompok_rko' => $request->kelompok_rko,
            // 'status_generik' =>$request->status_generik,
            // 'status_forkid' =>$request->status_forkid,
            // 'status_fornas' =>$request->status_fornas,
            // 'kekuatan_dosis' =>$request->kekuatan_dosis,
            // 'volumesediaan' =>$request->volumesediaan,
            // 'kelas_terapi' =>$request->kelas_terapi,
            // 'nilai_kdn' =>$request->nilai_kdn,
            // 'sertifikatkdn' =>$request->sertifikatkdn,
            // 'sistembayar' =>$request->sistembayar,
        );
        if ($request->has('kelasterapis')) {
            foreach ($request->kelasterapis as $key) {
                $simpanrinci = Mapingkelasterapi::firstOrCreate([
                    'kd_obat' => $simpan->kd_obat,
                    'kelas_terapi' => $key['kelasterapi']
                ]);
            }
        }
        if ($request->has('indikasis')) {
            foreach ($request->indikasis as $key) {
                $simpanrinci = IndikasiObat::firstOrCreate([
                    'kd_obat' => $simpan->kd_obat,
                    'indikasi' => $key['indikasi']
                ]);
            }
        }
        if (!$simpan) {
            return new JsonResponse(['message' => 'data gagal disimpan'], 500);
        }
        return new JsonResponse(['message' => 'data berhasil disimpan'], 200);
    }

    public function hapus(Request $request)
    {
        $hapus = Mobatnew::find($request->id)->update(['flag' => '1']);

        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function list()
    {
        // return new JsonResponse(request()->all());
        $list = Mobatnew::with('mkelasterapi', 'indikasi')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('merk', 'Like', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('kandungan', 'Like', '%' . request('q') . '%');
            })
            ->when(request('status_prb') == 'true', function ($q) {
                $q->where('status_prb', '1');
            })
            ->orderBy('id', 'desc')
            ->where('flag', '')
            ->paginate(request('per_page'));

        return new JsonResponse($list);
    }

    public function cariobat()
    {

        $query = Mobatnew::select(
            'kd_obat as kodeobat',
            'nama_obat as namaobat',
            'satuan_k',
            'satuan_b',
        )->where('flag', '')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%');
            })->orderBy('nama_obat')
            ->limit(50)
            ->get();
        return new JsonResponse($query);
    }
    public function cariObatHarga()
    {
        $query = Mobatnew::select(
            'kd_obat',
            'nama_obat as namaobat',
            'satuan_k',
            'satuan_b',
        )
            ->where('flag', '')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%');
            })
            ->with([
                'onestok' => function ($q) {
                    $q->select('kdobat', 'harga', 'nopenerimaan')
                        ->where('harga', '>', 0)
                        ->orderBy('id', 'desc');
                }
            ])
            ->when(request('konsinyasi') === '1', function ($q) {
                $q->where('status_konsinyasi', '1');
            })
            ->orderBy('nama_obat')
            ->limit(50)
            ->get();
        return new JsonResponse($query);
    }

    public function hapusMapingTerapi(Request $request)
    {
        $data = Mapingkelasterapi::find($request->id);
        if (!$data) {
            return new JsonResponse(['message' => 'Tidak ada data yang bisa dihapus'], 422);
        }
        $data->delete();
        return new JsonResponse(['message' => 'Kelas Terapi dihapus'], 200);
    }
    public function hapusMapingIndikasi(Request $request)
    {
        $data = IndikasiObat::find($request->id);
        if (!$data) {
            return new JsonResponse(['message' => 'Data belum masuk server'], 422);
        }
        $data->delete();
        return new JsonResponse(['message' => 'Indikasi dihapus'], 200);
    }

    public function mapingBpjs()
    {

        $raw = BridgingbpjsHelper::get_url('vclaim', 'referensi/obatprb/' . request('q'));
        $data = [];
        if ($raw['metadata']['code'] == '200') {
            $data = $raw['result']->list;
        }

        // return new JsonResponse($data);
        return new JsonResponse([
            'raw' => $raw,
            'data' => $data,
        ]);
    }
    public function insertMapingBpjs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'kode_bpjs' => 'required',
        ]);

        if ($validator->fails()) {
            // return new JsonResponse(['status' => false, 'message' => $validator->errors()], 422);
            return new JsonResponse($validator->errors(), 422);
        }
        $data = Mobatnew::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan',
                'request' => $request->all(),
            ], 410);
        }

        $data->kode_bpjs = $request->kode_bpjs;
        $data->save();
        return new JsonResponse($data);
    }
}
