<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Sp3b;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Akuntansi\Sp3b\Sp3b;
use App\Models\Siasik\Akuntansi\Sp3b\Sp3b_rinci;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Sp3bController extends Controller
{
    public function getdata() {

        $tahunawal=Carbon::createFromFormat('Y-m-d', request('tahun').'-'.request('bulan').'-01')->format('Y');
        $awal=request('tahun').'-'.request('bulan').'-01';
        $akhir = Carbon::createFromFormat('Y-m-d', $awal)->endOfMonth()->format('Y-m-d');
        $sebelum = Carbon::createFromFormat('Y-m-d', $awal)->subDay();
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tahun').'-'.request('bulan').'-01')->format('Y');
        if($tahunawal !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        $pagupendapatan = Tampung_pendapatan::where('tahun', $tahunawal)
        ->select('t_tampung_pendapatan.koderekeningblud',
                'akun50_2024.kodeall3 as kode6',
                'akun50_2024.uraian as uraian6',
                DB::raw('SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 3) as kode'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 3) LIMIT 1) as uraian'),
                )
        ->join('akun50_2024', 'akun50_2024.kodeall3', 't_tampung_pendapatan.koderekeningblud')
        ->groupBy('kode')
        ->get();

        $sebelumpendapatan = Create_JurnalPosting::where('jurnal_postingotom.kode', 'LIKE', '4.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        ->select('jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian as uraian6',
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as subtotal'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) LIMIT 1) as uraian')
        )
        ->whereBetween('jurnal_postingotom.tanggal', [$tahunawal.'-01-01', $sebelum])
        ->groupBy( 'kode')
        ->orderBy('kode', 'asc')
        ->get();

        $sebelumbelanja = Create_JurnalPosting::where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->select('jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) LIMIT 1) as uraian'),
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as subtotal')
            )
        ->whereBetween('jurnal_postingotom.tanggal', [$tahunawal.'-01-01', $sebelum])
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        ->groupBy( 'kode')
        ->orderBy('kode', 'asc')
        ->get();

        $sebelumpenyesuaian = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$tahunawal.'-01-01', $sebelum])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'jurnalumum_rinci.kodepsap13 as kode6',
                'jurnalumum_rinci.uraianpsap13 as uraian6',
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as subtotal'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) as kode'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) LIMIT 1) as uraian'))
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '4.' . '%')
        ->orWhere('jurnalumum_rinci.kodepsap13', 'LIKE', '5.' . '%')
        ->groupBy( 'kode')
        ->get();

        $sebelumpembiayaan = SisaAnggaran::where('tahun', request('tahun'))
        ->select('silpa.tanggal',
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(silpa.koderek50, ".", 3) LIMIT 1) as uraian'),
                DB::raw('SUBSTRING_INDEX(silpa.koderek50, ".", 3) as kode'),
                DB::raw('sum(silpa.nominal) as total'))
        ->whereBetween('silpa.tanggal', [$tahunawal.'-01-01', $sebelum])
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'silpa.koderek50')
        ->groupBy('kode')
        ->get();

        $pendapatan = Create_JurnalPosting::where('jurnal_postingotom.kode', 'LIKE', '4.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        ->select('jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian as uraian6',
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as subtotal'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) LIMIT 1) as uraian')
        )
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->groupBy( 'kode')
        ->orderBy('kode', 'asc')
        ->get();

        $penyeseuaian = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'jurnalumum_rinci.kodepsap13 as kode6',
                'jurnalumum_rinci.uraianpsap13 as uraian6',
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as subtotal'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) as kode'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) LIMIT 1) as uraian'))
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '4.' . '%')
        ->orWhere('jurnalumum_rinci.kodepsap13', 'LIKE', '5.' . '%')
        ->groupBy( 'kode')
        ->get();

        $belanja = Create_JurnalPosting::where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->select('jurnal_postingotom.tanggal',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) LIMIT 1) as uraian'),
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as subtotal')
            )
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        ->groupBy( 'kode')
        ->orderBy('kode', 'asc')
        ->get();

        $pembiayaan = SisaAnggaran::where('tahun', request('tahun'))
        ->select('silpa.tanggal',
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(silpa.koderek50, ".", 3) LIMIT 1) as uraian'),
                DB::raw('SUBSTRING_INDEX(silpa.koderek50, ".", 3) as kode'),
                DB::raw('sum(silpa.nominal) as total'))
        ->whereBetween('silpa.tanggal', [$awal, $akhir])
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'silpa.koderek50')
        ->groupBy('kode')
        ->get();

        $data = [
            'pagupendapatan' => $pagupendapatan,
            'pendapatan' => $pendapatan,
            'belanja' => $belanja,
            'pembiayaan' => $pembiayaan,
            'penyesuaian' => $penyeseuaian,
            'sebelumpendapatan' => $sebelumpendapatan,
            'sebelumbelanja' => $sebelumbelanja,
            'sebelumpenyesuaian' => $sebelumpenyesuaian,
            'sebelumpembiayaan' => $sebelumpembiayaan,
        ];
        return new JsonResponse ($data);
    }
    // public function getdata() {
    // $tahun = request('tahun');
    // $bulan = request('bulan');

    // // Tanggal awal dan akhir untuk bulan ini
    // $awal = $tahun . '-' . $bulan . '-01';
    // $akhir = $tahun . '-' . $bulan . '-31';

    // // Tanggal awal dan akhir untuk data sebelumnya (1 Januari hingga akhir bulan sebelumnya)
    // $sebelumAwal = $tahun . '-01-01';
    // $sebelumAkhir = Carbon::createFromFormat('Y-m-d', $awal)->subMonth()->endOfMonth()->format('Y-m-d');

    // // Fungsi untuk mengambil data dari periode tertentu
    // $getData = function($table, $kodePrefix, $awal, $akhir, $isPendapatan = false) {
    //     return $table::where('jurnal_postingotom.kode', 'LIKE', $kodePrefix . '%')
    //         ->where('jurnal_postingotom.verif', '=', '1')
    //         ->select(
    //             'jurnal_postingotom.tanggal',
    //             DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode'),
    //             'jurnal_postingotom.uraian',
    //             DB::raw($isPendapatan ? 'sum(jurnal_postingotom.kredit - jurnal_postingotom.debit) as subtotal' : 'sum(jurnal_postingotom.debit) as subtotal')
    //         )
    //         ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
    //         ->groupBy('kode')
    //         ->orderBy('kode', 'asc')
    //         ->get();
    // };

    // // Ambil data bulan ini
    // $pendapatan = $getData(Create_JurnalPosting::class, '4.', $awal, $akhir, true);
    // $belanja = $getData(Create_JurnalPosting::class, '5.', $awal, $akhir);
    // $pembiayaan = SisaAnggaran::where('tahun', $tahun)
    //     ->join('akun50_2024', 'akun50_2024.kodeall3', '=', DB::raw('SUBSTRING_INDEX(silpa.koderek50, ".", 3)'))
    //     ->select(
    //         'silpa.tanggal',
    //         DB::raw('SUBSTRING_INDEX(silpa.koderek50, ".", 3) as kode'),
    //         'akun50_2024.uraian',
    //         DB::raw('sum(silpa.nominal) as total')
    //     )
    //     ->whereBetween('silpa.tanggal', [$awal, $akhir])
    //     ->groupBy('kode')
    //     ->get();

    // // Ambil data sebelumnya (1 Januari hingga akhir bulan sebelumnya)
    // $sebelumpendapatan = $getData(Create_JurnalPosting::class, '4.', $sebelumAwal, $sebelumAkhir, true);
    // $sebelumbelanja = $getData(Create_JurnalPosting::class, '5.', $sebelumAwal, $sebelumAkhir);
    // $sebelumpembiayaan = SisaAnggaran::where('tahun', $tahun)
    //     ->join('akun50_2024', 'akun50_2024.kodeall3', '=', DB::raw('SUBSTRING_INDEX(silpa.koderek50, ".", 3)'))
    //     ->select(
    //         'silpa.tanggal',
    //         DB::raw('SUBSTRING_INDEX(silpa.koderek50, ".", 3) as kode'),
    //         'akun50_2024.uraian',
    //         DB::raw('sum(silpa.nominal) as total')
    //     )
    //     ->whereBetween('silpa.tanggal', [$sebelumAwal, $sebelumAkhir])
    //     ->groupBy('kode')
    //     ->get();

    // // Fungsi untuk menghitung nilai penyesuaian
    // $hitungPenyesuaian = function($data, $awal, $akhir) {
    //     $data->each(function ($item) use ($awal, $akhir) {
    //         $penyesuaian = Create_JurnalPosting::where('jurnal_postingotom.kode', 'LIKE', $item->kode . '%')
    //             ->where('jurnal_postingotom.verif', '=', '1')
    //             ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
    //             ->join('jurnalumum_rinci', 'jurnalumum_rinci.kodepsap13', '=', 'jurnal_postingotom.kode')
    //             ->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', '=', 'jurnalumum_rinci.nobukti')
    //             ->select(DB::raw('sum(jurnalumum_rinci.kredit - jurnalumum_rinci.debet) as totalpenyesuaian'))
    //             ->where('jurnalumum_heder.verif', '=', '1')
    //             ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan%')
    //             ->first();

    //         $item->totalpenyesuaian = $penyesuaian ? $penyesuaian->totalpenyesuaian : 0;
    //         $item->total = $item->subtotal + $item->totalpenyesuaian;
    //     });
    // };

    // // Hitung nilai penyesuaian untuk pendapatan bulan ini
    // $hitungPenyesuaian($pendapatan, $awal, $akhir);

    // // Hitung nilai penyesuaian untuk pendapatan sebelumnya
    // $hitungPenyesuaian($sebelumpendapatan, $sebelumAwal, $sebelumAkhir);

    // // Gabungkan data
    // $data = [
    //     'pendapatan' => $pendapatan,
    //     'belanja' => $belanja,
    //     'pembiayaan' => $pembiayaan,
    //     'sebelumpendapatan' => $sebelumpendapatan,
    //     'sebelumbelanja' => $sebelumbelanja,
    //     'sebelumpembiayaan' => $sebelumpembiayaan,
    // ];

    //     return new JsonResponse($data);
    // }

    public function listdata() {
        $tahunawal=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $tahun=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $data = Sp3b::with('rincians')
        ->whereBetween('tanggal', [$tahunawal.'-01-01', $tahun.'-12-31'])
        ->orderBy('nosp3b', 'asc')
        ->get();
         return new JsonResponse ($data);
    }

    public function savedata(Request $request){

        $validator = Validator::make($request->all(), [
            'nosp3b' => 'required|max:5|unique:siasik.sp3b,nosp3b'
        ], [
            'nosp3b.max' => 'Nomor SP3B harus terdiri dari 5 karakter.',
            'nosp3b.unique' => 'Nomor SP3B sudah digunakan.'
        ]);

        if ($validator->fails()) {
            return new JsonResponse($validator->errors(), 422);
        }

        $bulanrealisasi=request('tahun').'-'.request('bulan').'-01';
        $tanggalTerakhir = date('Y-m-t', strtotime($bulanrealisasi));
        $date = date('Y-m-d H:i:s');
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;

        $nosp3b = $request->nosp3b.request('bulan').'/SP3B/03.0301.01/'.request('tahun');
         // Pengecekan manual apakah nomor SP3B sudah ada
        if (Sp3b::where('nosp3b', $nosp3b)->exists()) {
            return new JsonResponse([
                'message' => 'Data SP3B Bulan Berkenaan Sudah Ada.'
            ], 422);
        }
        try {
            DB::beginTransaction();

            $save = Sp3b::create([
                'nosp3b' =>$nosp3b,
                'tanggal' =>$request->tanggal ?? '',
                'bulan_realisasi'=>$tanggalTerakhir ?? '',
                'tgl_entry' =>$date,
                'user_entry' => $pegawai ?? '',
                'pendapatan' =>$request->pendapatan ?? '',
                'realisasi' =>$request->realisasi ?? '',
                'pembiayaan' =>$request->pembiayaan ?? '',
                'saldoawal' =>$request->saldoawal ?? '',
                'kunci' =>'1',
            ]);
            foreach ($request->rincians as $rinci){
                $save->rincians()->create(
                    [
                        'nosp3b' => $nosp3b,
                        'kode'=>$rinci['kode'] ?? '',
                        'uraian'=>$rinci['uraian'] ?? '',
                        'keterangan'=>$rinci['keterangan'] ?? '',
                        'total'=>$rinci['total'] ?? '',
                        'user_entry' => $pegawai ?? '',
                        'tgl_entry'=>$date ?? '',
                    ]
                );
            }
            DB::commit();
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan...!!!',
                    'result' => $save,
                    'rincian' =>  $rinci
                ], 200);
        } catch (\Exception $er) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan',
                'error' => $er
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();

            $nosp3b = $request->nosp3b; // Ambil nosp3b dari payload

            // Hapus data di tabel sp3b_rinci yang terkait
            Sp3b_rinci::where('nosp3b', $nosp3b)->delete();

            // Hapus data di tabel sp3b
            Sp3b::where('nosp3b', $nosp3b)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Data SP3B dan rincian berhasil dihapus.',
            ], 200);

        } catch (\Exception $er) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus data.',
                'error' => $er
            ], 500);
        }
    }
}
