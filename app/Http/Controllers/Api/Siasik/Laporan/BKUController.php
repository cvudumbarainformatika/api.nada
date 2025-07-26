<?php

namespace App\Http\Controllers\Api\Siasik\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_Kepmendg50;
use App\Models\Siasik\Master\PejabatTeknis;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiLS\NpkLS_rinci;
use App\Models\Siasik\TransaksiPendapatan\DataSTS;
use App\Models\Siasik\TransaksiPendapatan\PendapatanLain;
use App\Models\Siasik\TransaksiPendapatan\PendapatanLainRinci;
use App\Models\Siasik\TransaksiPendapatan\PengeluaranKas;
use App\Models\Siasik\TransaksiPendapatan\TranskePPK;
use App\Models\Siasik\TransaksiPjr\CpPanjar_Header;
use App\Models\Siasik\TransaksiPjr\CpSisaPanjar_Header;
use App\Models\Siasik\TransaksiPjr\GeserKas_Header;
use App\Models\Siasik\TransaksiPjr\Nihil;
use App\Models\Siasik\TransaksiPjr\NpkPanjar_Header;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Header;
use App\Models\Siasik\TransaksiPjr\SPM_GU;
use App\Models\Siasik\TransaksiPjr\SpmUP;
use App\Models\Siasik\TransaksiSaldo\SaldoAwal_PPK;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;

class BKUController extends Controller
{
    public function ptk()
    {
        $thn= Carbon::createFromFormat('Y-m-d', request('tahun').'-'. request('bulan').'-01')->format('Y');
        // $thn=date('Y');
        $ptk = PejabatTeknis::where('flag', '!=', '1')
        // ->when(request('tahun'), function($x) use ($thn){
        //     $x
        // })
        ->where('tahun', $thn)
        ->get();
        return new JsonResponse($ptk);
    }
    public function bkuppk()
    {
        $thnsekarang=date('Y');
        $thn=Carbon::createFromFormat('Y-m-d', request('tahun').'-'. request('bulan').'-01')->format('Y');
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir = Carbon::createFromFormat('Y-m-d', $awal)->endOfMonth()->format('Y-m-d');
        $awalsebelum = Carbon::createFromFormat('Y-m-d', $awal)->subMonth()->startOfMonth()->format('Y-m-d');
        $akhirsebelum = Carbon::createFromFormat('Y-m-d', $awal)->subMonth()->endOfMonth()->format('Y-m-d');

        $saldo = SaldoAwal_PPK::where('rekening', '=', '0121161061')
        ->whereBetween('tanggal', [$awal, $akhir])
        ->get();

        $setor=TranskePPK::select('idtrans', 'tgltrans', 'nilai', 'ket')
        ->orderBy('tgltrans', 'asc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $kaskecil=PengeluaranKas::where('kd_kas', 'K0002')
        ->select('pengeluarankhaskecil.nominal',
                'pengeluarankhaskecil.tanggalpengeluaran',
                'pengeluarankhaskecil.kd_kas',
                'pengeluarankhaskecil.nomorpengeluaran')
        ->whereBetween('tanggalpengeluaran', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();


        $npkls = NpkLS_heder::select('npkls_heder.nonpk',
        'npkls_heder.tglpindahbuku',
        'npkls_heder.nopencairan')
        ->with('npklsrinci', function($npk)
            {
                $npk
                ->select('npkls_rinci.nonpk',
                'npkls_rinci.kegiatanblud',
                'npkls_rinci.nonpdls',
                'npkls_rinci.total')
                ->with('npdlshead', function ($npdrinci){
                    $npdrinci->select('npdls_heder.nonpdls',
                    'npdls_heder.kegiatanblud')
                    ->with('npdlsrinci', function($rinci){
                        $rinci->select('npdls_rinci.nonpdls',
                        'npdls_rinci.nominalpembayaran',
                        'npdls_rinci.koderek50',
                        'npdls_rinci.rincianbelanja',
                        'npdls_rinci.tglentry');

                    });
                });
            })
        ->whereBetween('tglpindahbuku', [$awal, $akhir])
        ->get();

        $nihil = Nihil::select(
            'pengembalianup.nopengembalian',
            'pengembalianup.tgltrans',
            'pengembalianup.jmlpengembalianreal')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();


        $spm = SpmUP::select('transSpm.tglSpm',
        'transSpm.noSpm',
        'transSpm.uraian',
        'transSpm.jumlahspp')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $spmgu = SPM_GU::select('transSpmgu.tglSpm',
        'transSpmgu.noSpm',
        'transSpmgu.uraian',
        'transSpmgu.jumlahspp')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $pegawai = Mpegawaisimpeg::whereIn('jabatan', ['J00001','J00005','J00034','J00035','J00192'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama',
                'pegawai.jabatan')
        ->orderBy('pegawai.jabatan', 'asc')
        ->get();

        $silpa=SisaAnggaran::select('silpa.notrans',
        'silpa.tanggal',
        'silpa.tahun',
        'silpa.nominal')
        ->where('tahun', $thnsekarang)
        ->where('tanggal', '!=', $thn.'-01'.'-01' )
        ->orderBy('tanggal', 'asc')
        ->whereBetween('tanggal', [$awal, $akhir])
        ->get();


        $saldosebelum = SaldoAwal_PPK::where('rekening', '=', '0121161061')
        ->select('nilaisaldo as nilai')
        ->whereBetween('tanggal', [$awalsebelum, $akhirsebelum])
        ->get();

        $setorsebelum=TranskePPK::select('idtrans','tgltrans', 'ket', 'nilai')

        ->whereBetween('tgltrans', [$awalsebelum, $akhirsebelum])
        ->get();


        $kaskecilsebelum=PengeluaranKas::where('kd_kas', 'K0002')
        ->select(
            'pengeluarankhaskecil.nominal',
                'pengeluarankhaskecil.tanggalpengeluaran',
                'pengeluarankhaskecil.kd_kas',
                'pengeluarankhaskecil.nomorpengeluaran',
                'nominal as nilai')
        ->whereBetween('tanggalpengeluaran', [$awalsebelum. ' 00:00:00', $akhirsebelum. ' 23:59:59'])

        ->get();

        $npklssebelum = NpkLS_heder::select(
                'npkls_heder.tglpindahbuku',
                'npkls_heder.nonpk',
                DB::raw('sum(total) as nilai'))
        ->join('npkls_rinci', 'npkls_rinci.nonpk', 'npkls_heder.nonpk')

        ->whereBetween('npkls_heder.tglpindahbuku', [$awalsebelum, $akhirsebelum])
        ->get();

        $nihilsebelum = Nihil::select(DB::raw('sum(jmlpengembalianreal) as nilai'), 'tgltrans')
        ->whereBetween('tgltrans', [$awalsebelum, $akhirsebelum])
        ->get();


        $spmsebelum = SpmUP::select(DB::raw('sum(jumlahspp) as nilai'))
        ->whereBetween('tglSpm', [$awalsebelum, $akhirsebelum])
        ->get();

        $spmgusebelum = SPM_GU::select(DB::raw('sum(jumlahspp) as nilai'), 'tglspm')
        ->whereBetween('tglSpm', [$awalsebelum, $akhirsebelum])
        ->get();


        $silpasebelum=SisaAnggaran::select(
            'silpa.notrans',
                    'silpa.tanggal',
                    'silpa.tahun',
                    'silpa.nominal as nilai')
        ->where('tahun', $thnsekarang)
        ->where('tanggal', '!=', $thn.'-01'.'-01' )
        ->whereBetween('tanggal', [$awalsebelum, $akhirsebelum])
        ->get();


        $saldoAkhirBulanSebelumnya = 0;

        // Hanya lakukan jika bukan bulan Januari
        if (request('bulan') != '01') {

            // Ambil data saldo akhir dari database (misalnya tabel SaldoAkhir)
            // Jika tidak ada tabel saldo akhir, kita harus menghitung dari transaksi bulan sebelumnya

            // Alternatif: Jika saldo akhir tidak disimpan dalam tabel terpisah,
            // hitung dari transaksi bulan sebelumnya dan simpan ke saldoAkhirBulanSebelumnya

            // Jika tidak ada data sebelumnya, tetap gunakan kalkulasi yang sudah ada
        if ($saldoAkhirBulanSebelumnya == 0) {
            // Ambil data transaksi dari bulan sebelumnya
            $tanggalAkhirBulanSebelumnya = Carbon::createFromFormat('Y-m-d', $awalsebelum)
                                            ->endOfMonth()->format('Y-m-d');

            // Query untuk mendapatkan transaksi sampai akhir bulan sebelumnya (dari awal tahun)
            $awalTahun = request('tahun').'-01-01';

            // Menghitung semua penerimaan sampai akhir bulan sebelumnya
            $totalPenerimaanSampaiSebelumnya =
                SaldoAwal_PPK::where('rekening', '=', '0121161061')
                    ->whereBetween('tanggal', [$awalTahun, $tanggalAkhirBulanSebelumnya])
                    ->sum('nilaisaldo') +
                TranskePPK::whereBetween('tgltrans', [$awalTahun, $tanggalAkhirBulanSebelumnya])
                    ->sum('nilai') +
                Nihil::whereBetween('tgltrans', [$awalTahun, $tanggalAkhirBulanSebelumnya])
                    ->sum('jmlpengembalianreal') +
                SisaAnggaran::where('tahun', $thnsekarang)
                    ->where('tanggal', '!=', $thn.'-01'.'-01')
                    ->whereBetween('tanggal', [$awalTahun, $tanggalAkhirBulanSebelumnya])
                    ->sum('nominal');

            // Menghitung semua pengeluaran sampai akhir bulan sebelumnya
            $totalPengeluaranSampaiSebelumnya =
                SpmUP::whereBetween('tglSpm', [$awalTahun, $tanggalAkhirBulanSebelumnya])
                    ->sum('jumlahspp') +
                SPM_GU::whereBetween('tglSpm', [$awalTahun, $tanggalAkhirBulanSebelumnya])
                    ->sum('jumlahspp') +
                NpkLS_heder::join('npkls_rinci', 'npkls_rinci.nonpk', '=', 'npkls_heder.nonpk')
                    ->whereBetween('tglpindahbuku', [$awalTahun, $tanggalAkhirBulanSebelumnya])
                    ->sum('npkls_rinci.total') +
                PengeluaranKas::where('kd_kas', 'K0002')
                    ->whereBetween('tanggalpengeluaran', [$awalTahun. ' 00:00:00', $tanggalAkhirBulanSebelumnya. ' 23:59:59'])
                    ->sum('nominal');

            // Hitung saldo akhir bulan sebelumnya
            $saldoAkhirBulanSebelumnya = $totalPenerimaanSampaiSebelumnya - $totalPengeluaranSampaiSebelumnya;
            }
        }
        $ppk = [
            'saldo' => $saldo,
            'silpa' => $silpa,
            'setor' => $setor,
            'kaskecil' => $kaskecil,
            'spm' => $spm,
            'spmgu' => $spmgu,
            'nihil' => $nihil,
            'npkls' => $npkls,
            'pegawai' => $pegawai,

            'saldosebelum' => $saldosebelum,
            'silpasebelum' => $silpasebelum,
            'setorsebelum' => $setorsebelum,
            'kaskecilsebelum' => $kaskecilsebelum,
            'spmsebelum' => $spmsebelum,
            'spmgusebelum' => $spmgusebelum,
            'nihilsebelum' => $nihilsebelum,
            'npklssebelum' => $npklssebelum,


            'saldoAkhirBulanSebelumnya' => $saldoAkhirBulanSebelumnya,


            'awalx'=> $awalsebelum,
            'akhirx'=> $akhirsebelum,
            'thn'=> $thn,
        ];

        return new JsonResponse($ppk);
    }
    public function bkupengeluaran()
    {
        $thnsekarang=date('Y');
        $thn=Carbon::createFromFormat('Y-m-d', request('tahun').'-'. request('bulan').'-01')->format('Y');
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        $akhirsebelum = Carbon::createFromFormat('Y-m-d', $awal)->subDay()->format('Y-m-d');
        $awalsebelum= Carbon::createFromFormat('Y-m-d', $awal)->subMonth()->format('Y-m-d');

        $npkls = NpkLS_heder::with(['npklsrinci'=> function($npk)
            {
                $npk->with(['npdlshead'=> function ($npdrinci){
                    $npdrinci->with(['npdlsrinci']);
                }]);
            }])
        ->whereBetween('tglpencairan', [$awal, $akhir])
        ->get();
        $pencairanls = NpkLS_heder::with(['npklsrinci'=> function($npk)
            {
                $npk->with(['npdlshead'=> function ($npdrinci){
                    $npdrinci->with(['npdlsrinci']);
                }]);
            }])
        ->whereBetween('tglpencairan', [$awal, $akhir])
        ->get();

        $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();

        $spm = SpmUP::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $spmgu = SPM_GU::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $npkpanjar=NpkPanjar_Header::with(['npkrinci'=> function($npd){
            $npd->with(['npdpjr_rinci']);
        }])
        ->whereBetween('tglnpk', [$awal, $akhir])
        ->get();


        $spjpanjar=SpjPanjar_Header::with(['spj_rinci'])
        ->whereBetween('tglspjpanjar', [$awal, $akhir])
        ->get();

        $pengembalianpjr=CpPanjar_Header::with(['cppjr_rinci'])
        ->whereBetween('tglpengembalianpanjar', [$awal, $akhir])
        ->get();

        $cpsisapjr=CpSisaPanjar_Header::with(['sisarinci'])
        ->whereBetween('tglpengembaliansisapanjar', [$awal, $akhir])
        ->get();


        $pergeserankas = GeserKas_Header::with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $pegawai = Mpegawaisimpeg::whereIn('jabatan', ['J00001','J00005','J00034','J00035'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->get();

        $nihil = Nihil::select(
            'nopengembalian',
            'tgltrans',
            'jmlup',
            'jmlspj',
            'jmlcp',
            'jmlpengembalianup',
            'jmlsisaup',
            'jmlpengembalianreal',)
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();



        $sblmnpk = NpkLS_heder::where('kunci', '=', '1')
        ->join('npkls_rinci', 'npkls_rinci.nonpk', 'npkls_heder.nonpk')
        ->select(DB::raw('IFNULL(sum(npkls_rinci.total),0) as total'))
        ->whereBetween('tglpencairan', [$awalsebelum, $akhirsebelum])
        // ->groupBy('npkls_heder.nonpk')
        ->get();

        $sblmpencairan = NpkLS_heder::where('npkls_heder.nopencairan', '!=', '')
        ->join('npkls_rinci', 'npkls_rinci.nonpk', 'npkls_heder.nonpk')
        ->select(DB::raw('IFNULL(sum(npkls_rinci.total),0) as total'))
        ->whereBetween('tglpencairan', [$awalsebelum, $akhirsebelum])
        // ->groupBy('npkls_heder.nonpk')
        ->get();

        $sblmcp = Contrapost::select(DB::raw('IFNULL(sum(nominalcontrapost),0) as total'))
        ->whereBetween('tglcontrapost', [$awalsebelum. ' 00:00:00', $akhirsebelum. ' 23:59:59'])
        ->get();

        $sblmspm = SpmUP::select(DB::raw('IFNULL(sum(jumlahspp),0) as total'))
        ->whereBetween('tglSpm', [$awalsebelum, $akhirsebelum])
        ->get();

        $sblmspmgu = SPM_GU::select(DB::raw('IFNULL(sum(jumlahspp),0) as total'))
        ->whereBetween('tglSpm', [$awalsebelum, $akhirsebelum])
        ->get();

        $sblmnpkpanjar=NpkPanjar_Header::where('npkpanjar_heder.nonpdpanjar', '!=','')
        ->join('npkpanjar_rinci', 'npkpanjar_rinci.nonpk', 'npkpanjar_heder.nonpk')
        ->select(DB::raw('IFNULL(sum(npkpanjar_rinci.total),0) as total'))
        ->whereBetween('tglnpk', [$awalsebelum, $akhirsebelum])
        ->get();

        $sblmspjpanjar=SpjPanjar_Header::where('spjpanjar_heder.verif', '=', '1')
        ->join('spjpanjar_rinci', 'spjpanjar_rinci.nospjpanjar', 'spjpanjar_heder.nospjpanjar')
        ->select(DB::raw('IFNULL(sum(spjpanjar_rinci.jumlahbelanjapanjar),0) as total'))
        ->whereBetween('tglspjpanjar', [$awalsebelum, $akhirsebelum])
        ->get();

        $sblmpengembalianpjr=CpPanjar_Header::join('pengembalianpanjar_rinci', 'pengembalianpanjar_rinci.nopengembalianpanjar', 'pengembalianpanjar_heder.nopengembalianpanjar')
        ->select(DB::raw('IFNULL(sum(pengembalianpanjar_rinci.sisapanjar),0) as total'))
        ->whereBetween('pengembalianpanjar_heder.tglpengembalianpanjar', [$awalsebelum, $akhirsebelum])
        ->get();

        $sblmcpsisapjr=CpSisaPanjar_Header::join('pengembaliansisapanjar_rinci', 'pengembaliansisapanjar_rinci.nopengembaliansisapanjar', 'pengembaliansisapanjar_heder.nopengembaliansisapanjar')
        ->select(DB::raw('IFNULL(sum(pengembaliansisapanjar_rinci.sisapanjar),0) as total'))
        ->whereBetween('pengembaliansisapanjar_heder.tglpengembaliansisapanjar', [$awalsebelum, $akhirsebelum])
        ->get();


        $sblmpergeserankas = GeserKas_Header::join('pergeseranTrinci', 'pergeseranTrinci.notrans', 'pergeseranTheder.notrans')
        ->select(DB::raw('IFNULL(sum(pergeseranTrinci.jumlah),0) as total'))
        ->whereBetween('pergeseranTheder.tgltrans', [$awalsebelum, $akhirsebelum])
        ->get();

        $sblmnihil = Nihil::select(DB::raw('IFNULL(sum(jmlpengembalianreal),0) as total'))
        ->whereBetween('tgltrans', [$awalsebelum, $akhirsebelum])
        ->get();

        $bkupengeluaran = [
            'npkls' => $npkls,
            'pencairanls' => $pencairanls,
            'cp' => $cp,
            'spm' => $spm,
            'spmgu' => $spmgu,
            'npkpanjar' => $npkpanjar,
            'spjpanjar' => $spjpanjar,
            'pengembalianpjr'=> $pengembalianpjr,
            'cpsisapjr' => $cpsisapjr,
            'pergeserankas' => $pergeserankas,
            'nihil' => $nihil,
            'pegawai' => $pegawai,


            'sblmnpk' => $sblmnpk,
            'sblmpencairan' => $sblmpencairan,
            'sblmcp' => $sblmcp,
            'sblmspm' => $sblmspm,
            'sblmspmgu' => $sblmspmgu,
            'sblmnpkpanjar' => $sblmnpkpanjar,
            'sblmspjpanjar' => $sblmspjpanjar,
            'sblmpengembalianpjr' => $sblmpengembalianpjr,
            'sblmcpsisapjr' => $sblmcpsisapjr,
            'sblmpergeserankas' => $sblmpergeserankas,
            'sblmnihil' => $sblmnihil
        ];

        return new JsonResponse($bkupengeluaran);
    }

    public function bkuptk()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';

        // cari where ... relasi pertma kedua tidak tampil jika kosong
        $pencairanls = NpkLS_heder::select('npkls_heder.*')
            ->when(request('ptk'),function($anu)
        {
            // $anu->whereHas('npklsrinci.npdlshead',function($hed){
            //     $hed->where('kodepptk', request('ptk'));
            // });

            $anu->join('npkls_rinci', 'npkls_rinci.nonpk','=','npkls_heder.nonpk')
                ->join('npdls_heder', 'npkls_rinci.nonpdls','=','npdls_heder.nonpdls')
                ->where('npdls_heder.kodepptk', request('ptk'))
                ->groupBy('npkls_rinci.nonpk');
        })->with(['npklsrinci'=> function($npk)
                {
                    $npk->when(request('ptk'),function($anu){
                        // $anu->whereHas('npdlshead',function($hed){
                        //     $hed->where('kodepptk', request('ptk'));
                        // });
                        $anu->join('npdls_heder', 'npdls_heder.nonpdls', '=','npkls_rinci.nonpdls')
                            ->where('npdls_heder.kodepptk', request('ptk'))
                            ->groupBy('npdls_heder.nonpdls');
                    })->with(['npdlshead'=> function ($npdrinci){
                        $npdrinci->with(['npdlsrinci']);
                    }]);
                }])
            ->whereBetween('npkls_heder.tglnpk', [$awal, $akhir])
            ->get();

        // $pencairanls = NpkLS_heder::when(request('ptk'),function($ada){
        //     $ada->with('npklsrinci.npdlshead.npdlsrinci')->whereHas('npklsrinci',function($npk)
        //     {
        //         $npk
        //         ->whereHas('npdlshead',function($hed){
        //             $hed->where('kodepptk', request('ptk'));
        //         })
        //         ->with('npdlshead', function ($npdrinci){
        //             $npdrinci
        //             ->with(['npdlsrinci']);
        //         });
        //     });
        // })
        // ->when(!request('ptk'), function($xx){
        //     $xx->with('npklsrinci.npdlshead');
        // })
        // ->whereBetween('tglnpk', [$awal, $akhir])
        // ->get();


        $npkpanjar = NpkPanjar_Header::select('npkpanjar_heder.*')
        ->when(request('ptk'),function($anu){
            // $anu->whereHas('npkrinci.npdpjr_head',function($hed){
            //     $hed->where('kodepptk', request('ptk'));
            // });
            $anu->join('npkpanjar_rinci', 'npkpanjar_rinci.nonpk', '=', 'npkpanjar_heder.nonpk')
                ->join('npdpanjar_heder', 'npdpanjar_heder.nonpdpanjar', '=', 'npkpanjar_rinci.nonpd')
                ->where('npdpanjar_heder.kodepptk', request('ptk'))
                ->groupBy('npkpanjar_rinci.nonpk');
        })->with(['npkrinci'=> function($npk)
                {
                    $npk->when(request('ptk'),function($anu){
                        // $anu->whereHas('npdpjr_head',function($hed){
                        //     $hed->where('kodepptk', request('ptk'));
                        // });
                        $anu->join('npdpanjar_heder', 'npdpanjar_heder.nonpdpanjar', '=', 'npkpanjar_rinci.nonpd')
                            ->where('npdpanjar_heder.kodepptk', request('ptk'))
                            ->groupBy('npdpanjar_heder.nonpdpanjar');
                    })->with(['npdpjr_head'=> function ($npdrinci){
                        $npdrinci->with(['npdpjr_rinci']);
                    }]);
                }])
            ->whereBetween('npkpanjar_heder.tglnpk', [$awal, $akhir])
            ->get();

        $spjpanjar=SpjPanjar_Header::with(['spj_rinci'])
        ->whereBetween('tglspjpanjar', [$awal, $akhir])
        ->where('kodepptk', request('ptk'))
        ->get();

        $pengembalianpjr=CpPanjar_Header::with(['cppjr_rinci'])
        ->whereBetween('tglpengembalianpanjar', [$awal, $akhir])
        ->where('kodepptk', request('ptk'))
        ->get();

        $cpsisapjr=CpSisaPanjar_Header::with(['sisarinci'])
        ->whereBetween('tglpengembaliansisapanjar', [$awal, $akhir])
        ->where('kodepptk', request('ptk'))
        ->get();

        $bkuptk = [
            'pencairanls' => $pencairanls,
            'npkpanjar' => $npkpanjar,
            'spjpanjar' => $spjpanjar,
            'pengembalianpjr' => $pengembalianpjr,
            'cpsisapjr' => $cpsisapjr,
        ];
        return new JsonResponse($bkuptk);

    }

    public function bukubank()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        $pencairanls = NpkLS_heder::with(['npklsrinci'=> function($npk)
            {
                $npk->with(['npdlshead'=> function ($npdrinci){
                    $npdrinci->with(['npdlsrinci']);
                }]);
            }])
        ->whereBetween('tglnpk', [$awal, $akhir])
        ->get();

        $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();

        $spm = SpmUP::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();

        $bankkas='Bank Ke Kas';
        $bankkekas = GeserKas_Header::where('jenis', $bankkas)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();


        $kasbank='Kas Ke Bank';
        $kaskebank = GeserKas_Header::where('jenis', $kasbank)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $spjpanjar=SpjPanjar_Header::with(['spj_rinci'])
        ->whereBetween('tglspjpanjar', [$awal, $akhir])
        ->get();

        $nihil = Nihil::select(
            'nopengembalian',
            'tgltrans',
            'jmlup',
            'jmlspj',
            'jmlcp',
            'jmlpengembalianup',
            'jmlsisaup',
            'jmlpengembalianreal',)
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $spmgu = SPM_GU::orderBy('tglSpm','desc')
        ->whereBetween('tglSpm', [$awal, $akhir])
        ->get();
        $pegawai = Mpegawaisimpeg::whereIn('jabatan', ['J00001','J00005','J00034','J00035'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->get();
        $bukubank = [
            'pencairanls' => $pencairanls,
            'cp' => $cp,
            'spm' => $spm,
            'bankkekas' => $bankkekas,
            'kaskebank'=> $kaskebank,
            'spjpanjar' => $spjpanjar,
            'nihil' => $nihil,
            'spmgu' => $spmgu,
            'pegawai' => $pegawai
        ];
        return new JsonResponse($bukubank);

    }
    public function bukutunai()
    {
        $awal=request('tahun').'-'. request('bulan').'-01';
        $akhir=request('tahun').'-'. request('bulan').'-31';
        // $pergeserankas = GeserKas_Header::with(['kasrinci'])
        // // $cp = Contrapost::orderBy('tglcontrapost','desc')
        // ->whereBetween('tgltrans', [$awal, $akhir])
        // ->get();

        $bankkas='Bank Ke Kas';
        $bankkekas = GeserKas_Header::where('jenis', $bankkas)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();


        $kasbank='Kas Ke Bank';
        $kaskebank = GeserKas_Header::where('jenis', $kasbank)->with(['kasrinci'])
        // $cp = Contrapost::orderBy('tglcontrapost','desc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        $npkpanjar=NpkPanjar_Header::with(['npkrinci'=> function($npd){
            $npd->with(['npdpjr_rinci']);
        }])
        ->whereBetween('tglnpk', [$awal, $akhir])
        ->get();

        $pengembalianpjr=CpPanjar_Header::with(['cppjr_rinci'])
        ->whereBetween('tglpengembalianpanjar', [$awal, $akhir])
        ->get();

        $cpsisapjr=CpSisaPanjar_Header::with(['sisarinci'])
        ->whereBetween('tglpengembaliansisapanjar', [$awal, $akhir])
        ->get();

        $pjr = 'PANJAR';
        $cp = Contrapost::where('jenisbelanja', $pjr)
        ->orderBy('tglcontrapost','desc')
        ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();

        $pegawai = Mpegawaisimpeg::whereIn('jabatan', ['J00001','J00005','J00034','J00035'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->get();

        $bukutunai = [
            // 'pergeserankas' => $pergeserankas,
            'bankkekas' => $bankkekas,
            'kaskebank' => $kaskebank,
            'npkpanjar' => $npkpanjar,
            'pengembalianpjr' => $pengembalianpjr,
            'cpsisapjr' => $cpsisapjr,
            'cp' => $cp,
            'pegawai' => $pegawai
        ];
        return new JsonResponse($bukutunai);
    }

    // coba appends
    public function kode(){
        $awal=request('tglmulai', '2024-03-01');
        $akhir=request('tglakhir', '2024-03-31');

        // $pencairanls = NpkLS_heder::whereHas('npklsrinci.npdlshead',function($hed)
        //     {
        //         $hed->where('kodepptk', request('ptk'));

        //     })->with('npklsrinci', function($npk)
        //         {
        //             $npk->whereHas('npdlshead',function($hed)
        //                 {
        //                     $hed->where('kodepptk', request('ptk'));

        //                 })->with('npdlshead', function ($npdrinci)
        //             {
        //             $npdrinci->with('npdlsrinci', function($rek)
        //                 {
        //                     $rek->orderBy('koderek50', 'asc');
        //                 });
        //             });
        //         })
        //     ->whereBetween('tglnpk', [$awal, $akhir])
        //     ->get();


        // $kodecoba = Akun50_2024::
        // whereHas('npdls_rinci.headerls',function ($cair){
        //     $cair->where('nopencairan', '!=', '');
        // })
        // ->with(['npdls_rinci' => function ($head){
        //     $head->whereHas('headerls',function ($cair){
        //         $cair->where('nopencairan', '!=', '');
        //     })->with('headerls',function($npk) {
        //         $npk->with('npkrinci',function($header) {
        //             $header->with('header',function($tgl){
        //                 $tgl->whereBetween('tglpencairan')->get();
        //             });
        //         });
        //     });
        // },
        // 'spjpanjar','cp'])
        // ->get();


        // $ls = Akun_Kepmendg50::with(['npdls_rinci'=> function($head)
        // {
        //     $head->whereHas('headerls',function($cair)
        //     {
        //         $cair->where('nopencairan', '!=', '');
        //     })->with(['headerls'=> function($where)
        //     {
        //         $where->where('kodebidang', request('kode')
        //     );
        //         // ->whereBetween('tglpencairan', [$awal, $akhir]);
        //     }]);
        // }])
        // ->where('kode1', '5')
        // // ->where('kode3', '02')
        // // ->limit(100)
        // ->get();

        // return ($kode);
        // $npd=NpdLS_rinci::with('cp')
        // ->where('nonpdls','00004/I/UMUM/NPD-LS/2022')
        // ->limit(50)
        // ->get();
        // return ($npd);
        $pencairanls = NpkLS_heder::when(request('kode'),function($anu)
        {
            $anu->whereHas('npklsrinci.npdlshead',function($hed){
                $hed->where('kodebidang', request('kode'));
            });
        })->when(request('keg'),function($anu){
            $anu->whereHas('npklsrinci.npdlshead',function($hed){
                $hed->where('kodebidang', request('kode'))
                ->where('kegiatanblud', request('keg'));
            });
        })->with(['npklsrinci'=> function($npk)
                {
                    $npk->when(request('kode'),function($anu){
                        $anu->whereHas('npdlshead',function($hed){
                            $hed->where('kodebidang', request('kode'));
                        });
                    })->when(request('keg'),function($anu){
                        $anu->whereHas('npdlshead',function($hed){
                            $hed->where('kodebidang', request('kode'))
                            ->where('kegiatanblud', request('keg'));
                        });
                    })
                    ->with(['npdlshead'=> function ($npdrinci){
                        $npdrinci->with('npdlsrinci',function($kode){
                            $kode->with('akun50');
                        });
                    }]);
                }])
            ->whereBetween('tglpencairan', [$awal, $akhir])
            ->get();

        return new JsonResponse($pencairanls);
    }

    public function coba(){
        // $coba = NpdLS_rinci::with(['akun'=> function($kode){
        //     $kode->where('kode1', '5')->get();
        // }]);
        // $x = NpdLS_rinci::where('koderek50');
        // $kode = Akun50_2024::with(['npdrinci'=>function($hed){
        //     $hed->where('koderek50');
        // }])
        // ->where('akun', '5')
        // // // ->where('kode4', '!=', '')
        // // // ->where('kode5', '!=', '')
        // // ->where('kode6', '!=', '')
        // ->limit(100)
        // ->get();

        $awal=request('tglmulai', '2024-03-01');
        $akhir=request('tglakhir', '2024-03-31');
        $setor=TranskePPK::orderBy('tgltrans', 'asc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();

        // $npd = NpdLS_rinci::with('akun50')
        // ->get();

        // $akun=Akun_Kepmendg50::all();
        // $filter = $akun->filter(function($kode){
        //     return $kode->kodeall == true;
        // });
        // $npd = NpdLS_rinci::where('koderek50')->with('akun', function($x) use ($filter){
        //     $x->where('kodeall', $filter);
        // })
        // ->get();

        return($setor);
    }

}
