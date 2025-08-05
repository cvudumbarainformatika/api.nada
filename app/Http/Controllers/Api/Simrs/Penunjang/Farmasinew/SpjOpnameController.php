<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Jabatan;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\SpjOpname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokOpnameFisik;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

class SpjOpnameController extends Controller
{
    //
    public function getOpname()
    {
        $now = request('tahun') . '-' . request('bulan');
        $kdop = Stokopname::select('kdobat')->where('tglopname', 'LIKE', '%' . $now . '%')->distinct('kdobat')->pluck('kdobat');
        $fis = StokOpnameFisik::select('kdobat')->where('tglopname', 'LIKE', '%' . $now . '%')->distinct('kdobat')->pluck('kdobat');
        $sm = array_unique(array_merge($kdop->toArray(), $fis->toArray()));
        $data['data'] = [];
        $raw = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'satuan_k',
        )->with([
            'oneopname' => function ($q) use ($now) {
                $q->select(
                    'kdobat',
                    'kdruang',
                    'harga',
                    'tglexp',
                    'tglopname',
                    DB::raw('sum(jumlah) as total')
                )->where('tglopname', 'LIKE', '%' . $now . '%')
                    ->groupBy('kdobat', 'kdruang', 'tglopname');
            },
            'onefisik' => function ($q) use ($now) {
                $q->where('tglopname', 'LIKE', '%' . $now . '%')
                    ->whereNotNull('kdruang');
            }
            // 'oneopname'

        ])
            ->where('flag', '')
            ->where(function ($x) {
                $x->where('nama_obat', 'like', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'like', '%' . request('q') . '%');
            })
            ->whereIn('kd_obat', $sm)
            ->orderBy('nama_obat', 'ASC')
            ->get();
        $data['tglopname'] = null;
        if (sizeof($sm) > 0) {
            foreach ($raw as $key) {
                if ($key->onefisik) {
                    if (isNull($data['tglopname'])) $data['tglopname'] = $key->onefisik->tglopname;
                    $key->jmlFisik = $key->onefisik->jumlah;
                    $key->keterangan = $key->onefisik->keterangan;
                    $key->kdruang = $key->onefisik->kdruang;
                }
                if ($key->oneopname) {
                    if (isNull($data['tglopname'])) $data['tglopname'] = $key->oneopname->tglopname;
                    $key->jmlOpname = $key->oneopname->total;
                    if (!$key->onefisik) $key->kdruang = $key->oneopname->kdruang;
                }
                $data['data'][] = $key;
            }
        } else {
            $data['data'] = $raw;
        }
        $data['req'] = request()->all();
        return new JsonResponse($data);
    }
    public function getOpnameDepo()
    {
        $data = request()->all();
        return new JsonResponse($data);
    }
    public function getKepala()
    {
        $data['farmasi'] = Petugas::select('id', 'nama', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', 'AKTIF')
            ->where(function ($q) {
                $q->where('jabatan', 'J00270')
                    ->orWhere('jabatan_tmb', 'JT00014');
            })
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->first();
        $data['keuangan'] = Petugas::select('id', 'nama', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', 'AKTIF')
            ->where('jabatan', 'J00005')
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->first();
        $apoteker = Jabatan::select('kode_jabatan')->where('jabatan', 'like', '%Apoteker%')->pluck('kode_jabatan');
        $data['pegawai'] = Petugas::select('nama', 'id', 'kdpegsimrs', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', '=', 'AKTIF')
            ->where('ruang', '=', 'R00025')
            ->whereIn('jabatan', $apoteker)
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->whereNotNull('satset_uuid')
            ->get();
        $data['pelaksanas'] = Petugas::select('nama', 'id', 'kdpegsimrs', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', '=', 'AKTIF')
            ->where('ruang', '=', 'R00025')
            ->where(function ($q) {
                $q->where('kdruangansim', 'like', '%Gd-05010100%')
                    ->orWhere('kdruangansim', 'like', '%Gd-04010103%')
                    ->orWhere('kdruangansim', 'like', '%Gd-04010102%')
                    ->orWhere('kdruangansim', 'like', '%Gd-03010101%')
                    ->orWhere('kdruangansim', 'like', '%Gd-03010100%')
                    ->orWhere('kdruangansim', 'like', '%Gd-05010101%')
                    ->orWhere('kdruangansim', 'like', '%Gd-04010104%');
            })
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->whereNotNull('satset_uuid')
            ->get();
        return new JsonResponse($data);
    }
    public function simpanPernyataan(Request $request)
    {
        $request->validate([
            'tglopname' => 'required',
            'no_sp' => 'required',
        ]);
        $data = SpjOpname::where('tglopname', '=', $request->tglopname)->first();
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan'
            ], 410);
        }
        $data->update([
            'no_sp' => $request->no_sp,
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
        ]);
        return new JsonResponse([
            'message' => 'Data berhasil disimpan',
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
    public function simpanBa(Request $request)
    {
        $request->validate([
            'tglopname' => 'required',
            'no_ba' => 'required',
            'peg_id_pj_so' => 'required',
        ]);
        $data = SpjOpname::updateOrCreate([
            'tglopname' => $request->tglopname,
        ], $request->all());
        return new JsonResponse([
            'message' => 'Data berhasil disimpan',
            'data' => $data,
            'req' => $request->all()
        ]);
    }
    public function getSpj()
    {
        $now = Carbon::create(request('tglopname'))->format('Y-m-d');
        $data = SpjOpname::where('tglopname', '=', $now)->first();
        return new JsonResponse($data);
    }
}
