<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\TransaksiLS\Notadinas_header;
use App\Models\Siasik\TransaksiLS\Notadinas_rinci;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotadinasController extends Controller
{
    public function listdata()
    {
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = Notadinas_header::whereBetween('notadinas_heder.tglnotadinas', [$tahunawal.'-01-01', $tahun.'-12-31']);

            if ($sa !== 'sa') {
                $data->where('notadinas_heder.kodepptk', $pegawai);
            }
            $nota=$data->join('notadinas_rinci', 'notadinas_rinci.nonotadinas', '=',  'notadinas_heder.nonotadinas')
            ->join('npdls_heder', 'npdls_heder.nonotadinas', '=',  'notadinas_heder.nonotadinas')
            ->with('rincians', function ($query) {
                $query->join('npdls_heder', 'npdls_heder.nonpdls', '=',  'notadinas_rinci.nonpdls')
                // ->leftJoin('npdls_rinci', 'npdls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
                ->select('npdls_heder.pptk',
                 'npdls_heder.penerima',
                 'npdls_heder.bidang',
                 'npdls_heder.tglnpdls',
                 'npdls_heder.keterangan',
                 'npdls_heder.nonpdls',
                 'notadinas_rinci.*',
                //  'npdls_rinci.*'
                )->with('npdlsrinci', function ($query) {
                    $query->join('akun50_2024', 'akun50_2024.kodeall2', 'npdls_rinci.koderek50')
                    ->select(
                        'npdls_rinci.nonpdls',
                        'npdls_rinci.nominalpembayaran as pengajuan',
                        'akun50_2024.kodeall3 as koderekening',
                        'akun50_2024.uraian as rekeningbelanja',
                        DB::raw('0 as pagu'), // Add pagu = 0
                        DB::raw('0 as realisasi') // Add realisasi = 0
                    );
                });
            })
            ->select('notadinas_heder.*',
                'npdls_heder.nonpk',
                'npdls_heder.nopencairan',
                DB::raw('SUM(notadinas_rinci.total) as total'),
            )
            ->when(request('q'), function($q){
                $q->where('notadinas_heder.nonotadinas', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('notadinas_heder.tglnotadinas', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('notadinas_heder.namapptk', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('notadinas_heder.bidang', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('notadinas_heder.kegiatan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('notadinas_rinci.total', 'LIKE', '%' . request('q') . '%')
                    ;
            })
            ->groupBy('notadinas_heder.nonotadinas')
            ->orderBy('notadinas_heder.tglnotadinas', 'desc')
            ->get();
        return new JsonResponse($nota);

    }



    public function selectNpd() {
        $npd = NpdLS_heder::where('kodepptk', request('kodepptk'))
        ->where('kodekegiatanblud', request('kodekegiatan'))
        ->where('kunci','=', '1')
        ->where('nonotadinas', '=', '')
        ->join('npdls_rinci', 'npdls_rinci.nonpdls', '=',  'npdls_heder.nonpdls')
        ->select('npdls_heder.*',
                DB::raw('SUM(npdls_rinci.nominalpembayaran) as total'),
                DB::raw('SUBSTRING_INDEX(npdls_rinci.koderek50, ".", 2) as kode'),
        )
        ->when(request('q'),function ($query) {
            $query
            ->where('npdls_heder.nonpdls', 'LIKE', '%' . request('q') . '%')
            ->where('npdls_rinci.nominalpembayaran', 'LIKE', '%' . request('q') . '%')
            ;
        })
        ->groupBy('npdls_heder.nonpdls')
        ->get();

        return new JsonResponse($npd);
    }

    public function savedata(Request $request)
    {
        if (empty($request->nonotadinas)) {
            DB::connection('siasik')->select('call notadinas(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('notadinas')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
            }
            $nomer = (int)$x->notadinas;
            $nonotadinas = FormatingHelper::nonotadinas($nomer, 'NOTA-DINAS');
        } else {
            $nonotadinas = $request->nonotadinas;
        }

        if (empty($request->nosptjm)) {
            DB::connection('siasik')->select('call nosptjm(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('nosptjm')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur nosptjm');
            }
            $nomer = (int)$x->nosptjm;
            $nosptjm = FormatingHelper::nonotadinas($nomer, 'SPTJ');
        } else {
            $nosptjm = $request->nosptjm;
        }

        if (empty($request->noverifikasi)) {
            DB::connection('siasik')->select('call noverifikasi(@nomor)');
            $x = DB::connection('siasik')->table('conter')->select('noverifikasi')->first();

            if (!$x) {
                throw new \Exception('Gagal mendapatkan nomor dari prosedur noverifikasi');
            }
            $nomer = (int)$x->noverifikasi;
            $noverifikasi = FormatingHelper::nonotadinas($nomer, 'VERIF-SPJ');
        } else {
            $noverifikasi = $request->noverifikasi;
        }
        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        try
        {
            DB::beginTransaction();
            $save = Notadinas_header::updateOrCreate
            (
                [
                    'nonotadinas' => $nonotadinas
                ],
                values: [
                    'nosptjm' => $nosptjm ?? '',
                    'noverifikasi' => $noverifikasi ?? '',
                    'tglnotadinas'=>$request->tglnotadinas ?? '',
                    'kodepptk'=>$request->kodepptk ?? '',
                    'namapptk'=>$request->namapptk ?? '',
                    'kodebidang'=>$request->kodebidang ?? '',
                    'bidang'=>$request->bidang ?? '',
                    'tglentry'=>$time ?? '',
                    'userentry'=>$pegawai ?? '',
                    'kodekegiatan'=>$request->kodekegiatan ?? '',
                    'kegiatan'=>$request->kegiatan ?? '',
                ]
            );
            $rincidata = [];
            foreach ($request->rincians as $rinci)
            {
                $save->rincians()->create(
                    [
                        'nonotadinas'=>$save->nonotadinas,
                        'nonpdls'=>$rinci['nonpdls'] ?? '',
                        'tglnpd'=>$rinci['tglnpd'] ?? '',
                        'kegiatan'=>$rinci['kegiatan'] ?? '',
                        'kodekegiatanblud'=>$rinci['kodekegiatanblud'] ?? '',
                        'kegiatanblud'=>$rinci['kegiatanblud'] ?? '',
                        'nokontrak'=>$rinci['nokontrak'] ?? '',
                        'noserahterima'=>$rinci['noserahterima'] ?? '',
                        'total'=>$rinci['total'] ?? '',
                        'tglentry'=>$time ?? '',
                        'userentry'=>$pegawai ?? '',
                    ]
                );
                $rincidata[] = $rinci['nonpdls'];
            }
            NpdLS_heder::where('nonpdls', $rincidata)
            ->update(['nonotadinas' => $save->nonotadinas]);
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
        $data = Notadinas_header::where('notadinas_heder.nonotadinas', request('nonotadinas'))
        ->select('notadinas_heder.nonotadinas',
                        'notadinas_heder.tglnotadinas',
                        'notadinas_rinci.*',
        )
        ->join('notadinas_rinci', 'notadinas_rinci.nonotadinas', 'notadinas_heder.nonotadinas')
        ->get();

        return new JsonResponse($data);
    }

    public function deleterinci(Request $request)
    {
        $header = Notadinas_header::
        where('nonotadinas', $request->nonotadinas)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'Nota Dinas Masih Terkunci'], 500);
        }


        if ($request->id) {
            $findrinci = Notadinas_rinci::where('id', $request->id)->first();
        } else {
            $findrinci = Notadinas_rinci::where('nonpdls', $request->nonpdls)->first();
        }

        if (!$findrinci) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        else {
        $findrinci->delete();
        NpdLS_heder::whereIn('nonpdls', [$request->nonpdls])->update(['nonotadinas' => '']);
        }
        $rinciAll = Notadinas_rinci::where('nonotadinas', $request->nonotadinas)->get();
        if(count($rinciAll) === 0){
            $header = Notadinas_header::where('nonotadinas', $request->nonotadinas)->first();

             if ($header) {
                $header->delete();
                NpdLS_heder::whereIn('nonotadinas', [$request->nonotadinas])->update(['nonotadinas' => '']);
                return new JsonResponse([
                    'message' => 'Data Berhasil dihapus',
                    'data' => []
                ], 200);
            } else {
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

    public function kuncidata (Request $request)
    {
        $request->validate([
            'nonotadinas' => 'required|string'
        ]);

        try {
            $header = Notadinas_header::where('nonotadinas', $request->nonotadinas)->first();
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

                if (!empty($header->terima)) {
                    return response()->json(['message' => 'Nota Dinas Sudah Terverifikasi'], 400);
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
            // Log::error('Gagal Membuka Kunci Serahterima: ' . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi Kesalahan saat Membuka Kunci',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function laprealisasi(){
        $tahun = Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $anggaran = PergeseranPaguRinci::where('tgl', $tahun)
        ->where('kodekegiatanblud', request('kodekegiatan'))
        ->where('pagu', '!=', '0')
        ->join('akun50_2024', 'akun50_2024.kodeall2', 't_tampung.koderek50')
        ->select('t_tampung.kodekegiatanblud',
                't_tampung.tgl',
                't_tampung.notrans',
                't_tampung.koderek108',
                't_tampung.uraian108',
                't_tampung.uraian50',
                't_tampung.usulan',
                't_tampung.volume',
                't_tampung.satuan',
                't_tampung.harga',
                't_tampung.pagu',
                't_tampung.idpp',
                'akun50_2024.kodeall3 as koderek50',)
                ->with(['jurnal','realisasi_spjpanjar'=> function ($realisasi) {
                    $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                        'spjpanjar_rinci.jumlahbelanjapanjar as realisasi',);
                    },'realisasi'=> function ($realisasi) {
                    $realisasi
                    ->join('npdls_heder', 'npdls_heder.nonpdls', '=',  'npdls_rinci.nonpdls')
                    // ->where('npdls_heder.nopencairan', '!=', '')
                    ->select(
                        'npdls_rinci.idserahterima_rinci',
                        'npdls_rinci.nominalpembayaran as realisasi');
                    },'contrapost'=> function ($realisasi) {
                    $realisasi->select('contrapost.idpp',
                                        'contrapost.nominalcontrapost as nilaicp',);
                    }])
        ->groupBy('t_tampung.idpp')
        ->get();
        return new JsonResponse($anggaran);
    }
}
