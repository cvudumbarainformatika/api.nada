<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\FormatingHelper;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Date;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_Kepmendg50;
use App\Models\Siasik\Master\Bagian;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use App\Models\Siasik\TransaksiLS\NewpajakNpdls;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Siasik\TransaksiLS\Serahterima_header;
use App\Models\Siasik\TransaksiLS\TransPajak;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use DateTime;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Format;

class NPD_LSController extends Controller
{
     public function bidang(){
        $thn= Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $bidang=Mapping_Bidang_Ptk_Kegiatan::where('tahun', $thn)
        ->where('alias', '!=', '')
        ->when(request('bidang'),function($keg) {
            $keg->where('kodebidang', request('bidang'));
        })
        ->select('kodebidang', 'bidang', 'kodekegiatan', 'kegiatan', 'kodepptk', 'namapptk')
        ->groupBy('kodekegiatan')
        ->get();

        // $bidangx = [
        //     'belanja' => $thn,
        //     'pendapatan' => $bidang,

        // ];

        return new JsonResponse($bidang);

    }
    public function listnpdls()
    {
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->nip;
        $sa = $pg->kdpegsimrs;
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = NpdLS_heder::whereBetween('tglnpdls', [$tahunawal . '-01-01', $tahun . '-12-31'])
        ->select(
            'npdls_heder.id',
                    'npdls_heder.nonpdls',
                    'npdls_heder.nonpk',
                    'npdls_heder.nopencairan',
                    'npdls_heder.nonotadinas',
                    'npdls_heder.tglnpdls',
                    'npdls_heder.pptk',
                    'npdls_heder.kodepptk',
                    'npdls_heder.bidang',
                    'npdls_heder.kodebidang',
                    'npdls_heder.kegiatanblud',
                    'npdls_heder.kodekegiatanblud',
                    'npdls_heder.penerima',
                    'npdls_heder.kodepenerima',
                    'npdls_heder.biayatransfer',
                    'npdls_heder.bast',
                    'npdls_heder.bank',
                    'npdls_heder.rekening',
                    'npdls_heder.npwp',
                    'npdls_heder.keterangan',
                    'npdls_heder.noserahterima',
                    'npdls_heder.nopencairan',
                    'npdls_heder.userentry',
                    'npdls_heder.serahterimapekerjaan',
                    'npdls_heder.kunci');
            if ($sa !== 'sa' && $sa !==  '1619' && $sa !==  '38' && $sa !==  '1618') {
                $data->where('kodepptk', $pegawai);
            }

            $data->when(request('q'), function ($query) {
                $query->where('nonpdls', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('tglnpdls', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('pptk', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('bidang', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('penerima', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('keterangan', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('nopencairan', 'LIKE', '%' . request('q') . '%');
            });

            // $data->;

            $npdls = $data->with([
                'npdlsrinci' => function ($rinci) {
                    $rinci->select(
                        'npdls_rinci.nonpdls',
                        'npdls_rinci.id',
                        'npdls_rinci.bast_r_id',
                        'npdls_rinci.nopenerimaan',
                        'npdls_rinci.koderek50',
                        'npdls_rinci.rincianbelanja',
                        'npdls_rinci.koderek108',
                        'npdls_rinci.uraian108',
                        'npdls_rinci.itembelanja',
                        'npdls_rinci.volumels',
                        'npdls_rinci.satuan',
                        'npdls_rinci.hargals',
                        'npdls_rinci.nominalpembayaran'
                    );
                },
                'npkrinci' => function ($cair) {
                    $cair->select('nonpk', 'nonpdls')
                            ->with('header', function ($header) {
                                $header->select('nonpk', 'tglpindahbuku');
                            });
                },
                'pajak',
                'newpajak'
            ])
            ->orderBy('tglnpdls', 'desc')
            ->get();
        return new JsonResponse($npdls);
    }
    // public function cetakPencairanNPD()
    // {
    //     $tahunawal=date('Y');
    //     $tahun=date('Y');
    //     $npdls = NpdLS_heder::select(
    //         'npdls_heder.nonpdls',
    //                 'npdls_heder.nonpk',
    //                 'npdls_heder.nopencairan',
    //                 'npdls_heder.tglnpdls',
    //                 'npdls_heder.pptk',
    //                 'npdls_heder.kodepptk',
    //                 'npdls_heder.bidang',
    //                 'npdls_heder.kegiatanblud',
    //                 'npdls_heder.penerima',
    //                 'npdls_heder.kodepenerima',
    //                 'npdls_heder.bank',
    //                 'npdls_heder.rekening',
    //                 'npdls_heder.npwp',
    //                 'npdls_heder.keterangan',
    //                 'npdls_heder.noserahterima',
    //                 'npdls_heder.nopencairan',
    //                 'npdls_heder.userentry',
    //                 'npdls_heder.serahterimapekerjaan')
    //         // ->where('userentry', $pegawai)

    //         ->where('nopencairan', '!=', '')
    //         ->whereBetween('tglnpdls', [$tahunawal.'-01-01', $tahun.'-12-31'])
    //         ->with(['npdlsrinci'=> function($rinci){
    //             $rinci->select('npdls_rinci.nonpdls',
    //                         'npdls_rinci.nopenerimaan',
    //                         'npdls_rinci.koderek50',
    //                         'npdls_rinci.rincianbelanja',
    //                         'npdls_rinci.koderek108',
    //                         'npdls_rinci.uraian108',
    //                         'npdls_rinci.itembelanja',
    //                         'npdls_rinci.volumels',
    //                         'npdls_rinci.satuan',
    //                         'npdls_rinci.hargals',
    //                         'npdls_rinci.nominalpembayaran');
    //         },'npkrinci'=>function($cair) {
    //             $cair->select('nonpk','nonpdls')
    //             ->with('header', function($header){
    //                 $header->select('nonpk', 'tglpindahbuku');
    //             });
    //         }])
    //         ->orderBy('tglnpdls', 'desc')
    //         ->get();
    //     return new JsonResponse($npdls);
    // }
    public function perusahaan()
    {
        $phk = Mpihakketiga::select('kode','nama','alamat','npwp','norek','bank','kodemapingrs','namasuplier','hidden')
        ->where('hidden', '!=', '1')
        ->when(request('q'),function ($query) {
            $query->where('nama', 'LIKE', '%' . request('q') . '%');
        })
        ->get();

        return new JsonResponse($phk);
    }
    // public function ptk()
    // {
    //     $tahun = Carbon::createFromFormat('Y-m-d', request('tahun'))->format('Y');
    //     // cari ptk kegiatan
    //     $cari = Mapping_Bidang_Ptk_Kegiatan::where('tahun', $tahun)->get();

    //     return new JsonResponse($cari);
    // }

    // BELUM DIPAKE
    public function anggaran(){
        $tahun = Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $anggaran = PergeseranPaguRinci::where('tgl', $tahun)
        // ->select('mappingpptkkegiatan.kegiatan','mappingpptkkegiatan.kodekegiatan')
        ->where('kodekegiatanblud', request('kodekegiatan'))
        ->where('pagu', '!=', '0')
        ->select('t_tampung.kodekegiatanblud',
                't_tampung.notrans',
                't_tampung.koderek50',
                't_tampung.koderek108',
                't_tampung.uraian108',
                't_tampung.uraian50',
                't_tampung.usulan',
                't_tampung.volume',
                't_tampung.satuan',
                't_tampung.harga',
                't_tampung.pagu',
                't_tampung.idpp')
                ->with(['jurnal','realisasi_spjpanjar'=> function ($realisasi) {
                    $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                        'spjpanjar_rinci.jumlahbelanjapanjar');
                    },'realisasi'=> function ($realisasi) {
                    $realisasi->select('npdls_rinci.idserahterima_rinci',
                                        'npdls_rinci.nominalpembayaran')
                                        // ->sum('nominalpembayaran')
                                        // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                        ;
                    },'contrapost'=> function ($realisasi) {
                    $realisasi->select('contrapost.idpp',
                                        'contrapost.nominalcontrapost');
                    }])
        // ->with('masterobat', function($sel){
        //     $sel->select('new_masterobat.kode108',
        //                 'new_masterobat.uraian108',
        //                 'new_masterobat.kd_obat')
        //         ->with('penerimaanrinci', function($data){
        //             $data->select('penerimaan_r.nopenerimaan',
        //             'penerimaan_r.kdobat',
        //             'penerimaan_r.harga_netto',
        //             'penerimaan_r.harga_netto_kecil',
        //             'penerimaan_r.jml_all_penerimaan',
        //             'penerimaan_r.subtotal');
        //         });
        // })
        // ->with('anggaran', function($pagu) use ($tahun){
        //     $pagu->where('tgl', $tahun)

        // })
        ->get();
        return new JsonResponse($anggaran);
    }
    public function bastfarmasi(){
        $tahun = Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // $tahun = date('Y');
        // $bast =  request('kodebast');
        $penerimaan=PenerimaanHeder::select('penerimaan_h.nobast',
                                            'penerimaan_h.tgl_bast',
                                            'penerimaan_h.nopenerimaan',
                                            'penerimaan_h.jumlah_bastx',
                                            'penerimaan_h.subtotal_bast',
                                            'penerimaan_h.jenis_penerimaan',
                                            'penerimaan_h.kdpbf',
                                            'penerimaan_h.no_npd',)

        // ->where('penerimaan_h.kdpbf', request('kodepenerima'), function ($bast){
        //     $bast->whereIn('penerimaan_h.jenis_penerimaan', ['Pesanan']);
        // })
        ->where('penerimaan_h.kdpbf', request('kodepenerima'))
        ->whereIn('penerimaan_h.jenis_penerimaan', ['Pesanan'])
        ->where('penerimaan_h.nobast', '!=', '')
        // ->where('penerimaan_h.no_npd', '=', '')
        ->whereNull('penerimaan_h.tgl_pencairan_npk')
        ->whereNotNull('penerimaan_h.tgl_bast')
        ->when(request('q'),function ($query) {
            $query->where('penerimaan_h.nopenerimaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('penerimaan_h.nobast', 'LIKE', '%' . request('q') . '%')
            ->orWhere('penerimaan_h.jumlah_bastx', 'LIKE', '%' . request('q') . '%')
            ->orWhere('penerimaan_h.subtotal_bast', 'LIKE', '%' . request('q') . '%');
        })
        ->with('rincianbast', function($rinci) use ($tahun) {
            $rinci
            // ->whereIn('nobast', [request('kodebast')])
                    ->select('bast_r.nobast',
                            'bast_r.nopenerimaan',
                            'bast_r.id',
                            'bast_r.kdobat',
                            'bast_r.harga_net',
                            'bast_r.jumlah',
                            'bast_r.subtotal'
                            // DB::raw('(harga_net * jumlah) as totalobat')
                            )
                            // ->selectRaw('sum(harga_net * jumlah) as totalobat')
        // ->with('penerimaanrinci', function($rinci) use ($tahun) {
        //     $rinci->select('penerimaan_r.nopenerimaan',
        //                     'penerimaan_r.kdobat',
        //                     'penerimaan_r.harga_netto',
        //                     'penerimaan_r.harga_netto_kecil',
        //                     'penerimaan_r.jml_all_penerimaan',
        //                     'penerimaan_r.subtotal')
                    ->with('masterobat',function ($rekening) use ($tahun){
                        $rekening->select('new_masterobat.kd_obat',
                                        'new_masterobat.kode50',
                                        'new_masterobat.uraian50',
                                        'new_masterobat.kode108',
                                        'new_masterobat.uraian108')
                            ->with(['jurnal','pagu'=> function ($pagu) use ($tahun) {
                            $pagu
                            ->where('tgl', $tahun)
                                ->where('kodekegiatanblud', request('kodekegiatanblud'))
                                ->where('pagu', '!=', '0')
                                ->select('t_tampung.kodekegiatanblud',
                                        't_tampung.notrans',
                                        't_tampung.koderek50',
                                        't_tampung.koderek108',
                                        't_tampung.uraian50',
                                        't_tampung.usulan',
                                        't_tampung.volume',
                                        't_tampung.satuan',
                                        't_tampung.harga',
                                        't_tampung.pagu',
                                        't_tampung.idpp')
                                        ->with(['realisasi_spjpanjar'=> function ($realisasi) {
                                            $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                                                'spjpanjar_rinci.jumlahbelanjapanjar');
                                            },'realisasi'=> function ($realisasi) {
                                            $realisasi->select('npdls_rinci.idserahterima_rinci',
                                                                'npdls_rinci.nominalpembayaran')
                                                                // ->sum('nominalpembayaran')
                                                                // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                                                ;
                                            },'contrapost'=> function ($realisasi) {
                                            $realisasi->select('contrapost.idpp',
                                                                'contrapost.nominalcontrapost');
                                            }]);
                            }]);
                    });
        })
        // ->orderBy('tgl_bast', 'DESC')
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        // ->paginate(request('per_page'));
        ->get();

        $konsinyasi = BastKonsinyasi::select('bast_konsinyasis.notranskonsi',
                                            'bast_konsinyasis.nobast',
                                            'bast_konsinyasis.kdpbf',
                                            'bast_konsinyasis.tgl_bast',
                                            'bast_konsinyasis.jumlah_bastx',)

        // ->where('bast_konsinyasis.kdpbf', request('kodepenerima'), function ($bast){
        //     $bast->where('bast_konsinyasis.nobast', '!=', '')
        //         // ->where('bast_konsinyasis.no_npd', '=', '')
        //         ->orWhere('bast_konsinyasis.tgl_pencairan_npk', '=', null);
        // })
        ->where('bast_konsinyasis.kdpbf', request('kodepenerima'))
        ->where(function ($query) {
            $query->where('bast_konsinyasis.nobast', '!=', '')
                ->orWhereNull('bast_konsinyasis.tgl_pencairan_npk');
        })
        ->whereNotNull('bast_konsinyasis.tgl_bast')
        ->when(request('q'),function ($query) {
            $query->where('bast_konsinyasis.nobast', 'LIKE', '%' . request('q') . '%')
            ->orWhere('bast_konsinyasis.notranskonsi', 'LIKE', '%' . request('q') . '%')
            ->orWhere('bast_konsinyasis.jumlah_bastx', 'LIKE', '%' . request('q') . '%');
        })
        ->with('rinci', function($rinci) use ($tahun) {
            $rinci
            // ->whereIn('notranskonsi', [request('kodebast')])
                    ->select('detail_bast_konsinyasis.notranskonsi',
                            'detail_bast_konsinyasis.id',
                            'detail_bast_konsinyasis.kdobat',
                            'detail_bast_konsinyasis.harga_net',
                            'detail_bast_konsinyasis.jumlah',
                            'detail_bast_konsinyasis.subtotal')
        // ->with('penerimaanrinci', function($rinci) use ($tahun) {
        //     $rinci->select('penerimaan_r.nopenerimaan',
        //                     'penerimaan_r.kdobat',
        //                     'penerimaan_r.harga_netto',
        //                     'penerimaan_r.harga_netto_kecil',
        //                     'penerimaan_r.jml_all_penerimaan',
        //                     'penerimaan_r.subtotal')
                    ->with('obat', function ($rekening) use ($tahun){
                        $rekening->select('new_masterobat.kd_obat',
                                        'new_masterobat.kode50',
                                        'new_masterobat.uraian50',
                                        'new_masterobat.kode108',
                                        'new_masterobat.uraian108')
                            ->with(['jurnal','pagu' => function ($pagu) use ($tahun) {
                            $pagu
                            ->where('tgl', $tahun)
                                ->where('kodekegiatanblud', request('kodekegiatanblud'))
                                ->where('pagu', '!=', '0')
                                ->select('t_tampung.kodekegiatanblud',
                                        't_tampung.notrans',
                                        't_tampung.koderek50',
                                        't_tampung.koderek108',
                                        't_tampung.uraian50',
                                        't_tampung.usulan',
                                        't_tampung.volume',
                                        't_tampung.satuan',
                                        't_tampung.harga',
                                        't_tampung.pagu',
                                        't_tampung.idpp')
                                        ->with(['realisasi_spjpanjar'=> function ($realisasi) {
                                            $realisasi->select('spjpanjar_rinci.iditembelanjanpd',
                                                                'spjpanjar_rinci.jumlahbelanjapanjar');
                                            },'realisasi'=> function ($realisasi) {
                                            $realisasi->select('npdls_rinci.idserahterima_rinci',
                                                                'npdls_rinci.nominalpembayaran')
                                                                // ->sum('nominalpembayaran')
                                                                // ->selectRaw('sum(nominalpembayaran) as total_realisasi')
                                                                ;
                                            },'contrapost'=> function ($realisasi) {
                                            $realisasi->select('contrapost.idpp',
                                                                'contrapost.nominalcontrapost');
                                            }]);
                            }]);
                    });
        })
        ->orderBy('nobast', 'asc')
        ->groupBy('nobast')
        // ->paginate(request('per_page'));
        ->get();

        $bast = [
            'penerimaan' => $penerimaan,
            'konsinyasi' => $konsinyasi,
        ];

        return new JsonResponse($bast);
    }

    public function bastpekerjaan(){
        $tahun = Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $data = Serahterima_header::where('serahterima_heder.kodepihakketiga', request('kodepenerima'))
        ->where('serahterima_heder.kodekegiatanblud', request('kodekegiatanblud'))
        ->whereBetween('serahterima_heder.tgltrans', [$tahun.'-01-01', $tahun.'-12-31'])
        ->where('serahterima_heder.kunci', '=', '1')
        ->where('serahterima_heder.nonpdls', '=', '')
        ->join('serahterima50', 'serahterima50.noserahterimapekerjaan', '=', 'serahterima_heder.noserahterimapekerjaan')
        ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 'serahterima_heder.kodekegiatanblud')
        ->join('pihak_ketiga', 'pihak_ketiga.kode', '=', 'serahterima_heder.kodepihakketiga')

        ->select(
            'serahterima_heder.*',
            'mappingpptkkegiatan.kodebidang',
            'mappingpptkkegiatan.bidang',
            'mappingpptkkegiatan.alias',
            'pihak_ketiga.bank',
            'pihak_ketiga.norek as rekening',
            'pihak_ketiga.npwp',
            DB::raw('SUM(serahterima50.nominalpembayaran) as nominalpembayaran')
        )
        ->groupBy('serahterima_heder.noserahterimapekerjaan')
        ->with('rinci'
            // , function ($rinci){
            //     $rinci->join('t_tampung', 't_tampung.idpp', '=', 'serahterima50.idserahterima_rinci')
            //     ->select(
            //         'serahterima50.*',
            //         't_tampung.koderek50',
            //         't_tampung.koderek108',
            //         't_tampung.uraian108',
            //         't_tampung.uraian50',
            //         't_tampung.volume',
            //         't_tampung.satuan',
            //         't_tampung.harga',
            //         't_tampung.pagu'
            //     );
            // }
        )
        ->get();
        return new JsonResponse($data);
    }


    public function coba(){
        // $nip = '196611251996032003';
        $akun=NpdLS_heder::all();
        $filter = $akun->filter(function($kode){
            return $kode->nip == true;
        });
        $pegawai=Pegawai::with(['npd_heder'=>function($x) use ($filter){
            $x->where('nip',$filter);
        }])->get();

        return new JsonResponse($pegawai);
    }
    public function simpannpd(Request $request)
    {
        $this->validate($request,[
                // 'nonpdls' => 'unique:siasik.npdls_heder,nonpdls',
                'keterangan' => 'required|min:3',
                'pptk' => 'required',
                'tglnpdls' => 'required',
                // 'nopenerimaan' => 'unique:siasik.npdls_rinci,nopenerimaan',
                // 'itembelanja' => 'required'
                ]);

        $time = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;

        $nomor = $request->nonpdls ?? self::buatnomor();

        $tanggal = $request->tglnpdls;
        $bulan = Carbon::parse($tanggal)->month;

        if ($bulan >= 1 && $bulan <= 3) {
            $triwulan = 'TRIWULAN 1';
        } elseif ($bulan >= 4 && $bulan <= 6) {
            $triwulan = 'TRIWULAN 2';
        } elseif ($bulan >= 7 && $bulan <= 9) {
            $triwulan = 'TRIWULAN 3';
        } else {
            $triwulan = 'TRIWULAN 4';
        }


        try {
            DB::beginTransaction();
            $save = NpdLS_heder::updateOrCreate(
                [
                    'nonpdls' => $nomor,
                ],
                [
                    'tglnpdls'=>$request->tglnpdls ?? '',
                    'kodepptk'=>$request->kodepptk ?? '',
                    'pptk'=>$request->pptk ?? '',
                    'serahterimapekerjaan'=>$request->serahterimapekerjaan ?? '',
                    'triwulan'=>$triwulan ?? '',
                    'program'=>'PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA',
                    'nokontrak'=>$request->nokontrak ?? '',
                    'kegiatan'=>'PELAYANAN DAN PENUNJANG PELAYANAN BLUD',
                    'kodekegiatanblud'=>$request->kodekegiatanblud ?? '',
                    'kegiatanblud'=>$request->kegiatanblud ?? '',
                    'kodepenerima'=>$request->kodepenerima ?? '',
                    'penerima'=>$request->penerima ?? '',
                    'bank'=>$request->bank ?? '',
                    'rekening'=>$request->rekening ?? '',
                    'npwp'=>$request->npwp ?? '',
                    'kodebidang'=>$request->kodebidang ?? '',
                    'bidang'=>$request->bidang ?? '',
                    'keterangan'=>$request->keterangan ?? '',
                    'biayatransfer'=>$request->biayatransfer ?? '',
                    'tglentry'=>$time ?? '',
                    'userentry'=>$pegawai ?? '',
                    'noserahterima'=>$request->noserahterima ?? '',
                    'bast'=>$request->bast ?? '',
                    // 'kunci'=>'1'
                ]);

                Serahterima_header::where('noserahterimapekerjaan', $request->noserahterima)
                    ->update(['nonpdls' => $save->nonpdls]);

            $penerimaans = [];
            foreach ($request->rincians as $rinci){

                $save->npdlsrinci()->create(

                    [
                        'nonpdls' => $save->nonpdls,
                    // ],
                    // [
                        'koderek50'=>$rinci['koderek50'] ?? '',
                        'rincianbelanja'=>$rinci['rincianbelanja'] ?? '',
                        'koderek108'=>$rinci['koderek108'] ?? '',
                        'uraian108'=>$rinci['uraian108'] ?? '',
                        'itembelanja'=>$rinci['itembelanja'] ?? '',
                        'nopenerimaan'=>$rinci['nopenerimaan'] ?? '',
                        'idserahterima_rinci'=>$rinci['idserahterima_rinci'] ?? '',
                        'tglentry'=>$time ?? '',
                        'userentry'=>$pegawai ?? '',
                        'volume'=>$rinci['volume'] ?? '',
                        'satuan'=>$rinci['satuan'] ?? '',
                        'harga'=>$rinci['harga'] ?? '',
                        'total'=>$rinci['total'] ?? '',
                        'volumels'=>$rinci['volumels'] ?? '',
                        'hargals'=>$rinci['hargals'] ?? '',
                        'totalls'=>$rinci['totalls'] ?? '',
                        'nominalpembayaran'=>$rinci['nominalpembayaran'] ?? '',
                        'bast_r_id'=>$rinci['bast_r_id'] ?? '',

                    ]);
                    //request nomer BAST
                    $penerimaans[]=$rinci['nopenerimaan'];
                }
                // update penerimaan atas nomer BAST FARMASI
                PenerimaanHeder::whereIn('nopenerimaan', $penerimaans)->update(['no_npd' => $save->nonpdls]);
                BastKonsinyasi::whereIn('notranskonsi', $penerimaans)->update(['no_npd' => $save->nonpdls]);

            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $save,
                    'penerimaans' => $penerimaans
                ], 200);
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan',
                'error' => $er
            ], 500);
        }
    }

    public function getlistformnpd()
    {
        $data = NpdLS_heder::where('npdls_heder.nonpdls', request('nonpdls'))
        // ->orWhere( 'npdls_rinci.bast_r_id', request('nopenerimaan'))
        ->select('npdls_heder.nonpdls',
                        'npdls_heder.tglnpdls',
                        'npdls_rinci.id',
                        'npdls_rinci.koderek50',
                        'npdls_rinci.rincianbelanja',
                        'npdls_rinci.koderek108',
                        'npdls_rinci.uraian108',
                        'npdls_rinci.itembelanja',
                        'npdls_rinci.nopenerimaan',
                        'npdls_rinci.volumels',
                        'npdls_rinci.satuan',
                        'npdls_rinci.hargals',
                        'npdls_rinci.totalls',
                        'npdls_rinci.nominalpembayaran',
                        'npdls_rinci.bast_r_id',
        )
        ->join('npdls_rinci', 'npdls_rinci.nonpdls', 'npdls_heder.nonpdls')
        ->get();

        return new JsonResponse($data);
    }
    public function kuncinpd (Request $request)
    {
        $request->validate([
            'nonpdls' => 'required|string'
        ]);

        try {
            $header = NpdLS_heder::where('nonpdls', $request->nonpdls)->first();
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

                if (!empty($header->verif || $header->nonotadinas)) {
                    return response()->json(['message' => 'NPD-LS Sudah Terverifikasi'], 400);
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
    public function deleterinci(Request $request)
    {
        $header = NpdLS_heder::
        where('nonpdls', $request->nonpdls)
        ->where('kunci', '!=', '')
        ->get();
        if(count($header) > 0){
            return new JsonResponse(['message' => 'NPD Masih Dikunci'], 500);
        }


        if ($request->id) {
            $findrinci = NpdLS_rinci::where('id', $request->id)->first();
        } else {
             $findrinci = NpdLS_rinci::where('nopenerimaan', $request->nopenerimaan)->first();
        }

        if (!$findrinci) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        }
        else {
        $findrinci->delete();
        PenerimaanHeder::whereIn('nopenerimaan', [$request->nopenerimaan])->update(['no_npd' => '']);
        BastKonsinyasi::whereIn('notranskonsi', [$request->nopenerimaan])->update(['no_npd' => '']);
        // return new JsonResponse(['message' => 'Data berhasil dihapus'], 200);
        }
        $rinciAll = NpdLS_rinci::where('nonpdls', $request->nonpdls)->get();
        if(count($rinciAll) === 0){
            $header = NpdLS_heder::where('nonpdls', $request->nonpdls)->first();

             if ($header) {
                $header->delete();
                PenerimaanHeder::whereIn('no_npd', [$request->nonpdls])->update(['no_npd' => '']);
                BastKonsinyasi::whereIn('no_npd', [$request->nonpdls])->update(['no_npd' => '']);
                Serahterima_header::whereIn('nonpdls', [$request->nonpdls])->update(['nonpdls' => '']);
                return new JsonResponse([
                    'message' => 'Data Berhasil dihapus',
                    'data' => []
                ], 200);
            } else {
                return new JsonResponse([
                    'message' => 'Data header tidak ditemukan',
                ], 404);
            }



            // $header->delete();
            // PenerimaanHeder::whereIn('no_npd', $request->nonpdls)->update(['no_npd' => '']);
            // BastKonsinyasi::whereIn('no_npd', $request->nonpdls)->update(['no_npd' => '']);
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus',
             'data' => $rinciAll
        ]);


        // $rinciAll = NpdLS_rinci::where('nonpdls', $request->nonpdls)->get();

        // if ($rinciAll->isEmpty()) {
        //     $header = NpdLS_heder::where('nonpdls', $request->nonpdls)->first();

        //     if ($header) {
        //         $header->delete();
        //         PenerimaanHeder::whereIn('no_npd', $request->nonpdls)->update(['no_npd' => '']);
        //         return new JsonResponse([
        //             'message' => 'Data Berhasil dihapus',
        //             'data' => []
        //         ], 200);
        //     } else {
        //         return new JsonResponse([
        //             'message' => 'Data header tidak ditemukan',
        //         ], 404);
        //     }
        // } else {
        //     return new JsonResponse([
        //         'message' => 'Data masih memiliki rinci',
        //     ], 400);
        // }

    }
    public static function buatnomor(){
        // $user = auth()->user()->pegawai_id;
        // $pg= Pegawai::find($user);
        // $pegawai= $pg->kdpegsimrs;
        // if($pegawai === ''){
        //     $pegawai = "RSUD";
        // }else{
        //     $pegawai= $pg->kdpegsimrs;
        // }
        // $x= $pg->bagian;
        // $bag=Bagian::select('kodebagian')->where('kodebagian', $x)->get();
        // $pegawai=$bag->kodebagian;

        $bidang = Mapping_Bidang_Ptk_Kegiatan::select('alias')->where('kodekegiatan', request('kodekegiatan'))->get();
        $instansi = ('RSUD');
        $huruf = ('NPD-LS');
        // $no = ('4.02.0.00.0.00.01.0000');
        date_default_timezone_set('Asia/Jakarta');
        // $tgl = date('Y/m/d');
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        $thn = date('Y');
        // $time = date('mis');
        // $nomer=Transaksi::latest();
        $cek = NpdLS_heder::count();
        if ($cek == null){
            $urut = "000001";
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($huruf).'/'.strtoupper($instansi).'/'.$thn;
        }
        else{
            $ambil=NpdLS_heder::all()->last();
            $urut = (int)substr($ambil->id, 0, 5) + 1;
            //cara menyambungkan antara tgl dn kata dihubungkan tnda .
            // $urut = "000" . $urut;
            if(strlen($urut) == 1){
                $urut = "00000" . $urut;
            }
            else if(strlen($urut) == 2){
                $urut = "0000" . $urut;
            }
            else if(strlen($urut) == 3){
                $urut = "000" . $urut;
            }
            else if(strlen($urut) == 4){
                $urut = "00" . $urut;
            }
            else if(strlen($urut) == 5){
                $urut = "0" . $urut;
            }
            else {
                $urut = (int)$urut;
            }
            $sambung = $urut.'/'.$rom[date('n')].'/'.strtoupper($huruf).'/'.strtoupper($instansi).'/'.$thn;
        }

        return $sambung;
    }

    public function selectpajak(){
        $getlist = Akun50_2024::where('akun50_2024.subrincian_objek', '!=', '')
        ->where(function ($data) {
            $data->where('akun50_2024.kodeall3', 'LIKE', '2.1.01.05.' . '%')
                ->orWhere('akun50_2024.kodeall3', 'LIKE', '2.1.01.06' . '%');
                })
        ->select('akun50_2024.kodeall2', 'akun50_2024.uraian', 'akun50_2024.kodeall3')
        ->when(request('q'), function($q){
            $q->where('akun50_2024.kodeall3','like','%'. request('q') . '%')
                ->orWhere('akun50_2024.kodeall2','like','%'. request('q') . '%')
                ->orWhere('akun50_2024.uraian','like','%'. request('q') . '%');
        })
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();
         return new JsonResponse ($getlist);
    }

    public function savepajakls(Request $request){
        $tgl = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;



        try {
            $nonpdls = $request->nonpdls;
        if ($nonpdls === '' || $nonpdls === null) {
            return new JsonResponse(['message' => 'Silahkan Input Data NPD!'], 500);
        } else {
            DB::beginTransaction();
            $save = NewpajakNpdls::updateOrCreate(
                [
                    'nonpdls' => $nonpdls,
                    'koderekening' => $request->koderekening,
                ],
                [
                    'uraian' => $request->uraian ?? '',
                    'nilai' => $request->nilai ?? '',
                    'userentry' => $pegawai,
                    'tglentry' => $tgl

                ]);
               return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $save
                ], 200);
        }

    } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan',
                'error' => $er
            ], 500);
        }

    }
    public function listpajak(){
        $data = NewpajakNpdls::where('nonpdls', request('nonpdls'))
        ->select('id', 'nonpdls', 'koderekening', 'uraian', 'nilai')
        ->orderBy('id', 'desc')
        ->get();
        return new JsonResponse ($data);
    }

    public function deletepajak(Request $request){
        $data = NewpajakNpdls::where('id', $request->id)->first();

        if (!$data) {
           return new JsonResponse(['message' => 'Data tidak ditemukan'], 404);
        } else {
            $data->delete();
        }

        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return new JsonResponse([
            'message' => 'Data Berhasil Dihapus',
            'data' => $data
        ]);
    }

    public function updateuser(){
    // INI UNTUK VERIF ALL //
        // $time = date('Y-m-d H:i:s');

        // update user di bast konsinyasi
        // $data = BastKonsinyasi::where('flag_bayar', '=', '1')
        // ->update(['user_bayar' => '1619',]);
        // ->get();

        // update tglverifdi jurnal
        // $datatime = Create_JurnalPosting::where('tglverif', '=', NULL)->update(['tglverif' => $time]);

        // return new JsonResponse ([
        //     // 'message' => 'Data Berhasil di Verifikasi',
        //     // 'datanpk' => $npk,
        //     'update' => $data,

        // ], 200);


        // Mulai transaksi database untuk memastikan konsistensi data
        DB::beginTransaction();

        try {
            // Ambil data dari NpkLS_rinci yang memenuhi kriteria
            $npkData = NpkLS_rinci::where('nopencairan', '!=', '')
                ->select('nonpdls', 'total', 'tglentrycair')
                ->get();

            // Proses update data PenerimaanHeder
            $updatedCount = 0;

            foreach ($npkData as $npk) {
                // Konversi format tanggal dari 'Y-m-d H:i:s' ke 'Y-m-d'
                $tglPencairan = Carbon::parse($npk->tglentrycair)->format('Y-m-d');

                // Update data PenerimaanHeder yang sesuai
                $updateResult = PenerimaanHeder::where('no_npd', $npk->nonpdls)
                ->whereNull('tgl_pencairan_npk')
                    // ->where('flag_bayar', '=', '')
                    ->update([
                        'tgl_pencairan_npk' => $tglPencairan,
                        // 'tgl_pembayaran' => $tglPencairan,
                        'nilai_pembayaran' => $npk->total,
                        'total_pembayaran' => $npk->total,
                        'user_bayar' => '1619',
                        'flag_bayar' => '1'
                    ]);

                // $updateResult = BastKonsinyasi::where('no_npd', $npk->nonpdls)
                //     ->whereNull('tgl_pencairan_npk')
                //     // ->where('flag_bayar', '=', '')
                //     ->update([
                //         'tgl_pencairan_npk' => $tglPencairan,
                //         'tgl_pembayaran' => $tglPencairan,
                //         'nilai_pembayaran' => $npk->total,
                //         'total_pembayaran' => $npk->total,
                //         'user_bayar' => '1619',
                //         'flag_bayar' => '1'
                //     ]);

                if ($updateResult) {
                    $updatedCount += $updateResult;
                }
            }

            // Commit transaksi jika semua berhasil
            DB::commit();

            // Ambil data terbaru untuk response
            // $updatedNpkData = NpkLS_rinci::where('nopencairan', '!=', '')
            //     ->select('nonpdls', 'total', 'tglentrycair')
            //     ->get();

            // $updatedPenerimaanData = PenerimaanHeder::where('no_npd', '!=', '')
            //     ->whereNotNull('tgl_pencairan_npk')
            //     ->select('no_npd', 'tgl_pencairan_npk', 'nilai_pembayaran', 'total_pembayaran', 'user_bayar', 'flag_bayar')
            //     ->get();

            return new JsonResponse([
                'message' => 'Update berhasil. Jumlah data diupdate: ' . $updatedCount, $updateResult,
                'datanpk' => $npkData,
                // 'updated_data' => $updatedPenerimaanData,
            ], 200);

        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            DB::rollBack();

            return new JsonResponse([
                'message' => 'Gagal melakukan update: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }

    }

    public function updateNopenerimaan()
    {
        // Ambil data dari penerimaan_h (server farmasi)
        $penerimaans = DB::connection('farmasi')
            ->table('penerimaan_h')
            ->where('no_npd', request('no_npd'))
            ->select('no_npd', 'nopenerimaan', 'subtotal_bast')
            ->get();

        // Update npdls_rinci (server siasik) berdasarkan data dari farmasi
        foreach ($penerimaans as $penerimaan) {
            DB::connection('siasik')
                ->table('npdls_rinci')
                ->where('nopenerimaan', '=', '')
                ->where('nonpdls', $penerimaan->no_npd)
                ->where('totalls', $penerimaan->subtotal_bast)
                ->update(['nopenerimaan' => $penerimaan->nopenerimaan]);
        }

        return response()->json(['message' => 'Update berhasil']);
    }
    // {
    //     $batchSize = 500; // Jumlah data per batch
    //     $offset = 0;

    //     do {
    //         // Ambil data dari penerimaan_h secara bertahap
    //         $penerimaans = DB::connection('farmasi')
    //             ->table('penerimaan_h')
    //             ->select('no_npd', 'nopenerimaan', 'subtotal_bast')
    //             ->offset($offset)
    //             ->limit($batchSize)
    //             ->get();

    //         if ($penerimaans->isEmpty()) break;

    //         // Update npdls_rinci untuk setiap batch
    //         foreach ($penerimaans as $penerimaan) {
    //             DB::connection('siasik')
    //                 ->table('npdls_rinci')
    //                 ->where('nopenerimaan', '=', '')
    //                 ->where('nonpdls', $penerimaan->no_npd)
    //                 ->where('totalls', $penerimaan->subtotal_bast)
    //                 ->update(['nopenerimaan' => $penerimaan->nopenerimaan]);
    //         }

    //         $offset += $batchSize;
    //     } while (true);

    //     return response()->json(['message' => 'Update berhasil']);
    // }
}
