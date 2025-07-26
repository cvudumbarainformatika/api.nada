<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\Kebidanan;
use App\Models\Simrs\Anamnesis\KeluhanNyeri;
use App\Models\Simrs\Anamnesis\Neonatal;
use App\Models\Simrs\Anamnesis\Pediatrik;
use App\Models\Simrs\Anamnesis\SkreeningGizi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnamnesisHomodialisaController extends Controller
{
    //

    public function list()
    {

        $data = self::getdata(request('noreg'));
        return new JsonResponse($data);
    }

    public static function getdata($noreg)
    {
        // $akun = auth()->user()->pegawai_id;
        // $nakes = Petugas::select('kdgroupnakes')->find($akun)->kdgroupnakes;

        $data = Anamnesis::select([
            'rs209.id',
            'rs209.rs1',
            'rs209.rs1 as noreg',
            'rs209.rs2 as norm',
            'rs209.rs3 as tgl',
            'rs209.rs4 as keluhanUtama',
            'rs209.riwayatpenyakit',
            'rs209.riwayatalergi',
            'rs209.keteranganalergi',
            'rs209.riwayatpengobatan',
            'rs209.riwayatpenyakitsekarang',
            'rs209.riwayatpenyakitkeluarga',
            'rs209.riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya',
            'rs209.kdruang',
            'rs209.awal',
            'rs209.user',
            'pegawai.nama as petugas',
            'pegawai.kdgroupnakes as nakes',
        ])
            ->leftJoin('kepegx.pegawai as pegawai', 'rs209.user', '=', 'pegawai.kdpegsimrs')
            ->where('rs209.rs1', '=', $noreg)
            //  ->where('rs209.kdruang','!=', 'POL014')
            //  ->where('pegawai.aktif', '=', 'AKTIF')
            ->with([
                'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                'keluhannyeri',
                'skreeninggizi',
                'neonatal',
                'pediatrik',
                'kebidanan'
            ])

            ->groupBy('rs209.id')
            ->get();

        return $data;
    }
    public static function getdataAwal($norm)
    {
        // $akun = auth()->user()->pegawai_id;
        // $nakes = Petugas::select('kdgroupnakes')->find($akun)->kdgroupnakes;

        $data = Anamnesis::select([
            'rs209.id',
            'rs209.rs1',
            'rs209.rs1 as noreg',
            'rs209.rs2 as norm',
            'rs209.rs3 as tgl',
            'rs209.rs4 as keluhanUtama',
            'rs209.riwayatpenyakit',
            'rs209.riwayatalergi',
            'rs209.keteranganalergi',
            'rs209.riwayatpengobatan',
            'rs209.riwayatpenyakitsekarang',
            'rs209.riwayatpenyakitkeluarga',
            'rs209.riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya',
            'rs209.kdruang',
            'rs209.awal',
            'rs209.user',
            'pegawai.nama as petugas',
            'pegawai.kdgroupnakes as nakes',
        ])
            ->leftJoin('kepegx.pegawai as pegawai', 'rs209.user', '=', 'pegawai.kdpegsimrs')
            ->where('rs209.rs2', '=', $norm)
            //  ->where('rs209.kdruang','!=', 'POL014')
            //  ->where('pegawai.aktif', '=', 'AKTIF')
            ->with([
                'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                'keluhannyeri',
                'skreeninggizi',
                'neonatal',
                'pediatrik',
                'kebidanan'
            ])

            ->where('rs209.awal', '1')
            ->where('rs209.kdruang', 'PEN005')

            ->groupBy('rs209.id')
            ->get();

        return $data;
    }

    public function simpananamnesis(Request $request)
    {
        $data = self::storeAnamnesis($request);
        return new JsonResponse($data);
    }

    public static function storeAnamnesis($request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;

        DB::beginTransaction();
        try {
            if ($request->id !== null) {
                $hasil = Anamnesis::where('id', $request->id)->update(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->norm,
                        'rs3' => date('Y-m-d H:i:s'),
                        'rs4' => $request->form['keluhanUtama'] ?? '',
                        'riwayatpenyakit' => $request->form['rwPenyDhl'] ?? '',
                        'riwayatalergi' => $request->form['rwAlergi'] ?? '', // array
                        'keteranganalergi' => $request->form['ketRwAlergi'] ?? '',
                        'riwayatpengobatan' => $request->form['rwPengobatan'] ?? '',
                        'riwayatpenyakitsekarang' => $request->form['rwPenySkr'] ?? '',
                        'riwayatpenyakitkeluarga' => $request->form['rwPenyKlrg'] ?? '',
                        'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->form['rwPkrjDgZatBahaya'] ?? '',
                        'skreeninggizi' => $request->skreeninggizi ?? 0,
                        'asupanmakan' => $request->asupanmakan ?? 0,
                        'kondisikhusus' => $request->kondisikhusus ?? '',
                        'skor' => $request->skor ?? 0,
                        'scorenyeri' => $request->skorNyeri ?? 0,
                        'keteranganscorenyeri' => $request->keluhanNyeri ?? '',
                        'kdruang' => 'PEN005',
                        'awal' => $request->awal ?? null,
                        'user'  => $kdpegsimrs,
                    ]
                );
                if ($hasil === 1) {
                    $simpananamnesis = Anamnesis::where('id', $request->id)->first();
                } else {
                    $simpananamnesis = null;
                }
            } else {
                $simpananamnesis = Anamnesis::create(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->norm,
                        'rs3' => date('Y-m-d H:i:s'),
                        'rs4' => $request->form['keluhanUtama'] ?? '',
                        'riwayatpenyakit' => $request->form['rwPenyDhl'] ?? '',
                        'riwayatalergi' => $request->form['rwAlergi'] ?? '', // array
                        'keteranganalergi' => $request->form['ketRwAlergi'] ?? '',
                        'riwayatpengobatan' => $request->form['rwPengobatan'] ?? '',
                        'riwayatpenyakitsekarang' => $request->form['rwPenySkr'] ?? '',
                        'riwayatpenyakitkeluarga' => $request->form['rwPenyKlrg'] ?? '',
                        'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->form['rwPkrjDgZatBahaya'] ?? '',
                        'kdruang' => 'PEN005',
                        'awal' => $request->awal ?? null,
                        'user'  => $kdpegsimrs,
                    ]
                );
            }




            // save nyeri
            $skorNyeri = 0;
            $ketNyeri = null;
            $skorNyeri = $request->form['keluhannyeri']['skorNyeri'] ?? 0;
            $ketNyeri = $request->form['keluhannyeri']['ket'] ?? null;


            KeluhanNyeri::updateOrCreate(
                ['rs209_id' => $simpananamnesis->id],
                [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'dewasa' => $request->form['keluhannyeri'] ?? null, // array
                    'kebidanan' => $request->formKebidanan['keluhannyeri'] ?? null, // array
                    'neonatal' => $request->formNeoNatal['keluhannyeri'] ?? null, // array
                    'pediatrik' => $request->formPediatrik['keluhannyeri'] ?? null, // array
                    'skor' => $skorNyeri,
                    'keluhan' => $ketNyeri,
                    'user_input' => $kdpegsimrs,
                    'group_nakes' => $user->kdgroupnakes

                ]
            );

            // save gizi
            $skor = 0;
            $ket = null;
            $skor = $request->form['skreeninggizi']['skor'] ?? 0;
            $ket = $request->form['skreeninggizi']['ket'] ?? null;


            SkreeningGizi::updateOrCreate(
                ['rs209_id' => $simpananamnesis->id],
                [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'dewasa' => $request->form['skreeninggizi'] ?? null, // array
                    'kebidanan' => $request->formKebidanan['skreeninggizi'] ?? null, // array
                    'neonatal' => $request->formNeoNatal['skreeninggizi'] ?? null, // array
                    'pediatrik' => $request->formPediatrik['skreeninggizi'] ?? null, // array
                    'skor' => $skor,
                    'keterangan' => $ket,
                    'user_input' => $kdpegsimrs,
                    'group_nakes' => $user->kdgroupnakes
                ]
            );




            DB::commit();
            // return new JsonResponse([
            //     'message' => 'BERHASIL DISIMPAN',
            //     'result' => self::getdata($request->noreg),
            // ], 200);
            if ($request->awal == '1') {
                $result = self::getDataAwal($request->norm);
            } else {
                $result = self::getdata($request->noreg);
            }

            $data = [
                'success' => true,
                'message' => 'BERHASIL DISIMPAN',
                'idAnamnesis' => $simpananamnesis->id,
                'result' => $result
            ];

            return $data;
        } catch (\Exception $th) {
            DB::rollBack();
            // return new JsonResponse(['message' => 'GAGAL DISIMPAN','err'=>$th], 500);
            $data = [
                'success' => false,
                'message' => 'GAGAL DISIMPAN',
                'result' => $th->getMessage(),
            ];

            return $data;
        }
    }

    public function hapusanamnesis(Request $request)
    {
        $cari = Anamnesis::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
        // return new JsonResponse($cari, 200);
    }

    public function historyanamnesis()
    {
        $raw = [];
        $history = Anamnesis::select(
            'id',
            'rs2 as norm',
            'rs3 as tgl',
            'rs4 as keluhanutama',
            'riwayatpenyakit',
            'riwayatalergi',
            'keteranganalergi',
            'riwayatpengobatan',
            'riwayatpenyakitsekarang',
            'riwayatpenyakitkeluarga',
            'skreeninggizi',
            'asupanmakan',
            'kondisikhusus',
            'skor',
            'scorenyeri',
            'keteranganscorenyeri',
            'user',
        )
            ->where('rs2', request('norm'))
            ->where('rs3', '<', Carbon::now()->toDateString())
            ->with('datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs')
            ->orderBy('tgl', 'DESC')
            ->get()
            ->chunk(10);

        $collapsed = $history->collapse();


        return new JsonResponse($collapsed->all());
    }
}
