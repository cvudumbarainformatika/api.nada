<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Perubahan_pak_header;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakAnggaranController extends Controller
{
    public function bidangbidangkegiatan(){
        $thn= request('tahun', 'Y');
        $bidangkegiatan=Mapping_Bidang_Ptk_Kegiatan::where('tahun', $thn)
        ->where('alias', '!=', '')
        ->when(request('bidang'),function($keg) {
            $keg->where('kodebidang', request('bidang'));
        })
        ->select('kodebidang', 'bidang', 'kodekegiatan', 'kegiatan', 'kodepptk', 'namapptk')
        ->groupBy('kodekegiatan')
        ->get();

        return new JsonResponse($bidangkegiatan);

    }

    public function getAnggaran() {
        $thn= request('tahun', 'Y');
        $anggaran = PergeseranPaguRinci::where('tgl', $thn)
        ->where('t_tampung.pagu', '!=', 0)
        ->where('t_tampung.bidang', request('bidang'))
        ->where('t_tampung.kodekegiatanblud', request('kegiatan'))
        ->select(
            't_tampung.usulan',
            't_tampung.pagu',
            't_tampung.koderek108',
            't_tampung.koderek50',
            't_tampung.kodekegiatanblud',
            't_tampung.volume',
            't_tampung.harga',
            't_tampung.satuan',
            'penyesesuaianperioritas_heder.pptk',
            'penyesesuaianperioritas_heder.kodepptk',
            'penyesesuaianperioritas_heder.capaianprogram',
            'penyesesuaianperioritas_heder.masukan',
            'penyesesuaianperioritas_heder.keluaran',
            'penyesesuaianperioritas_heder.hasil',
            'penyesesuaianperioritas_heder.targetcapaian',
            'penyesesuaianperioritas_heder.targetkeluaran',
            'penyesesuaianperioritas_heder.targethasil',
            'akun50_2024.kodeall3 as kode',
            'akun50_2024.uraian as uraian'
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 5) as kode5'))
        ->join('akun50_2024', 'akun50_2024.kodeall2', '=', 't_tampung.koderek50')
        ->join('penyesesuaianperioritas_heder', 'penyesesuaianperioritas_heder.kodekegiatan', '=', 't_tampung.kodekegiatanblud')
        ->with(['lvl1' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        }, 'lvl2' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        },'lvl3' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        },'lvl4' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        },'lvl5' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        }])
        ->orderBy('kode', 'asc')
        ->get();

        return new JsonResponse($anggaran);
    }

    public function getRka() {
        $tahun = request('tahun', 'Y');
        $rkaawal = Penyesuaian_Prioritas_Header::whereBetween('penyesesuaianperioritas_heder.tgltrans', [$tahun.'-01-01', $tahun.'-12-31'])
            ->where('penyesesuaianperioritas_heder.kodebidang', request('bidang'))
            ->where('penyesesuaianperioritas_heder.kodekegiatan', request('kegiatan'))
            ->with(['rincian' => function($query) {
                $query->join('akun50_2024', 'akun50_2024.kodeall2', '=', 'penyesesuaianperioritas_rinci.koderek50')
                    ->select(
                        'penyesesuaianperioritas_rinci.id as idpp',
                        'penyesesuaianperioritas_rinci.notrans',
                        'penyesesuaianperioritas_rinci.usulan',
                        'penyesesuaianperioritas_rinci.koderek108',
                        'penyesesuaianperioritas_rinci.uraian108',
                        'penyesesuaianperioritas_rinci.jumlahacc as volume',
                        'penyesesuaianperioritas_rinci.harga',
                        'penyesesuaianperioritas_rinci.nilai as total',
                        'penyesesuaianperioritas_rinci.satuan',
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                        'akun50_2024.kodeall3 as kode6',
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 1) LIMIT 1) as uraian1'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 2) LIMIT 1) as uraian2'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 3) LIMIT 1) as uraian3'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 4) LIMIT 1) as uraian4'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 5) LIMIT 1) as uraian5'),
                        'akun50_2024.uraian as uraian6'
                    );
            }, 'rincianpergeseran' => function($query) {
                $query->join('akun50_2024', 'akun50_2024.kodeall2', '=', 'perubahanrincianbelanja.koderek50')
                    ->select(
                        'perubahanrincianbelanja.id',
                        'perubahanrincianbelanja.idpp',
                        'perubahanrincianbelanja.notrans',
                        'perubahanrincianbelanja.usulan',
                        'perubahanrincianbelanja.koderek108',
                        'perubahanrincianbelanja.uraian108',
                        'perubahanrincianbelanja.satuan',
                        'perubahanrincianbelanja.volumebaru',
                        'perubahanrincianbelanja.hargabaru',
                        'perubahanrincianbelanja.totalbaru',
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                        'akun50_2024.kodeall3 as kode6',
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 1) LIMIT 1) as uraian1'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 2) LIMIT 1) as uraian2'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 3) LIMIT 1) as uraian3'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 4) LIMIT 1) as uraian4'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(perubahanrincianbelanja.koderek50, ".", 5) LIMIT 1) as uraian5'),
                        'akun50_2024.uraian as uraian6'
                    )
                    ->havingRaw('perubahanrincianbelanja.id = (SELECT MAX(id) FROM perubahanrincianbelanja prb2 WHERE prb2.idpp = perubahanrincianbelanja.idpp)');
            }])
            ->get();

        $datapak = Perubahan_pak_header::whereBetween('usulanHonor_h_pak.tglTransaksi', [$tahun.'-01-01', $tahun.'-12-31'])
            ->where('usulanHonor_h_pak.kodebagian', request('bidang'))
            ->where('usulanHonor_h_pak.kodeKegiatan', request('kegiatan'))
            ->with(['rincipak' => function($query){
                $query->join('akun50_2024', 'akun50_2024.kodeall2', '=', 'usulanHonor_r_pak.koderek50')
                    ->select(
                        'usulanHonor_r_pak.idpp',
                        'usulanHonor_r_pak.notrans',
                        'usulanHonor_r_pak.keterangan as usulan',
                        'usulanHonor_r_pak.koderek108',
                        'usulanHonor_r_pak.uraian108',
                        'usulanHonor_r_pak.volume',
                        'usulanHonor_r_pak.harga',
                        'usulanHonor_r_pak.nilai as total',
                        'usulanHonor_r_pak.satuan',
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                        'akun50_2024.kodeall3 as kode6',
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 1) LIMIT 1) as uraian1'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 2) LIMIT 1) as uraian2'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 3) LIMIT 1) as uraian3'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 4) LIMIT 1) as uraian4'),
                        DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 5) LIMIT 1) as uraian5'),
                        'akun50_2024.uraian as uraian6'
                    );
            }])
            ->get();

        // Menggabungkan rincian dan rincianpergeseran ke hasilpergeseran
        $combinedData = $rkaawal->map(function ($item) {
            // Ambil idpp langsung dari properti model sebelum konversi
            $rincianData = $item->rincian->isNotEmpty() ? $item->rincian->map(function ($rincian) {
                $data = $rincian->toArray(); // Gunakan toArray() untuk konversi yang lebih aman
                $data['idpp'] = (string) $rincian->idpp; // Ambil idpp dari properti model
                return $data;
            })->keyBy('idpp')->all() : [];

            $pergeseranData = $item->rincianpergeseran->isNotEmpty() ? $item->rincianpergeseran->map(function ($pergeseran) {
                $data = $pergeseran->toArray();
                return $data;
            })->keyBy('idpp')->all() : [];

            $hasilpergeseran = [];

            // Tambahkan semua rincian sebagai dasar
            if (!empty($rincianData)) {
                foreach ($rincianData as $idpp => $rincian) {
                    $pergeseran = $pergeseranData[$idpp] ?? null;
                    $hasilpergeseran[] = [
                        'idpp' => $idpp,
                        'usulan' => $rincian['usulan'] ?? '',
                        'koderek108' => $rincian['koderek108'] ?? '',
                        'uraian108' => $rincian['uraian108'] ?? '',
                        'volume' => $rincian['volume'] ?? 0,
                        'harga' => $rincian['harga'] ?? 0,
                        'total' => $rincian['total'] ?? 0,
                        'satuan' => $rincian['satuan'] ?? '',
                        'volumebaru' => $pergeseran['volumebaru'] ?? $rincian['volume'] ?? 0,
                        'hargabaru' => $pergeseran['hargabaru'] ?? $rincian['harga'] ?? 0,
                        'totalbaru' => $pergeseran['totalbaru'] ?? $rincian['total'] ?? 0,
                        'kode1' => $rincian['kode1'] ?? '',
                        'kode2' => $rincian['kode2'] ?? '',
                        'kode3' => $rincian['kode3'] ?? '',
                        'kode4' => $rincian['kode4'] ?? '',
                        'kode5' => $rincian['kode5'] ?? '',
                        'kode6' => $rincian['kode6'] ?? '',
                        'uraian1' => $rincian['uraian1'] ?? '',
                        'uraian2' => $rincian['uraian2'] ?? '',
                        'uraian3' => $rincian['uraian3'] ?? '',
                        'uraian4' => $rincian['uraian4'] ?? '',
                        'uraian5' => $rincian['uraian5'] ?? '',
                        'uraian6' => $rincian['uraian6'] ?? '',
                    ];
                }
            }

            // Tambahkan rincianpergeseran yang tidak ada di rincian
            if (!empty($pergeseranData)) {
                foreach ($pergeseranData as $idpp => $pergeseran) {
                    if (!isset($rincianData[$idpp])) {
                        $hasilpergeseran[] = [
                            'idpp' => $idpp,
                            'usulan' => $pergeseran['usulan'] ?? '',
                            'koderek108' => $pergeseran['koderek108'] ?? '',
                            'uraian108' => $pergeseran['uraian108'] ?? '',
                            'volume' => 0,
                            'harga' => 0,
                            'total' => 0,
                            'satuan' => $pergeseran['satuan'] ?? '',
                            'volumebaru' => $pergeseran['volumebaru'] ?? 0,
                            'hargabaru' => $pergeseran['hargabaru'] ?? 0,
                            'totalbaru' => $pergeseran['totalbaru'] ?? 0,
                            'kode1' => $pergeseran['kode1'] ?? '',
                            'kode2' => $pergeseran['kode2'] ?? '',
                            'kode3' => $pergeseran['kode3'] ?? '',
                            'kode4' => $pergeseran['kode4'] ?? '',
                            'kode5' => $pergeseran['kode5'] ?? '',
                            'kode6' => $pergeseran['kode6'] ?? '',
                            'uraian1' => $pergeseran['uraian1'] ?? '',
                            'uraian2' => $pergeseran['uraian2'] ?? '',
                            'uraian3' => $pergeseran['uraian3'] ?? '',
                            'uraian4' => $pergeseran['uraian4'] ?? '',
                            'uraian5' => $pergeseran['uraian5'] ?? '',
                            'uraian6' => $pergeseran['uraian6'] ?? '',
                        ];
                    }
                }
            }

            return [
                'id' => $item->id,
                'notrans' => $item->notrans,
                'kodepptk' => $item->kodepptk,
                'pptk' => $item->pptk,
                'kodebidang' => $item->kodebidang,
                'namabidang' => $item->namabidang,
                'kodekegiatan' => $item->kodekegiatan,
                'kegiatan' => $item->kegiatan,
                'capaianprogram' => $item->capaianprogram,
                'masukan' => $item->masukan,
                'keluaran' => $item->keluaran,
                'hasil' => $item->hasil,
                'targetcapaian' => $item->targetcapaian,
                'targetkeluaran' => $item->targetkeluaran,
                'targethasil' => $item->targethasil,
                'hasilpergeseran' => $hasilpergeseran,
            ];
        })->all();


    // Membuat objek baru perubahanpak berdasarkan rincipak dan hasilpergeseran
    $finalData = collect($combinedData)->map(function ($item) use ($datapak) {
        $rincianData = collect($item['hasilpergeseran'])->keyBy('idpp')->all();
        $rincipakData = $datapak->isNotEmpty() ? $datapak->flatMap(function ($pakItem) {
            return $pakItem->rincipak->map(function ($rincipak) {
                $data = $rincipak->toArray();
                $data['idpp'] = (string) $rincipak->idpp;
                return $data;
            });
        })->keyBy('idpp')->all() : [];

        $perubahanpak = [];

        if (!empty($rincianData)) {
            foreach ($rincianData as $idpp => $rincian) {
                $rincipak = $rincipakData[$idpp] ?? null;
                $perubahanpak[] = [
                    'idpp' => $idpp,
                    'usulan' => $rincian['usulan'] ?? '',
                    'koderek108' => $rincian['koderek108'] ?? '',
                    'uraian108' => $rincian['uraian108'] ?? '',
                    'volume' => $rincian['volumebaru'] ?? 0,
                    'harga' => $rincian['hargabaru'] ?? 0,
                    'total' => $rincian['totalbaru'] ?? 0,
                    'satuan' => $rincian['satuan'] ?? '',
                    'volumebaru' => $rincipak['volume']  ?? 0,
                    'hargabaru' => $rincipak['harga']  ?? 0,
                    'totalbaru' => $rincipak['total']  ?? 0,
                    'kode1' => $rincian['kode1'] ?? '',
                    'kode2' => $rincian['kode2'] ?? '',
                    'kode3' => $rincian['kode3'] ?? '',
                    'kode4' => $rincian['kode4'] ?? '',
                    'kode5' => $rincian['kode5'] ?? '',
                    'kode6' => $rincian['kode6'] ?? '',
                    'uraian1' => $rincian['uraian1'] ?? '',
                    'uraian2' => $rincian['uraian2'] ?? '',
                    'uraian3' => $rincian['uraian3'] ?? '',
                    'uraian4' => $rincian['uraian4'] ?? '',
                    'uraian5' => $rincian['uraian5'] ?? '',
                    'uraian6' => $rincian['uraian6'] ?? '',
                ];
            }
        }

        if (!empty($rincipakData)) {
            foreach ($rincipakData as $idpp => $rincipak) {
                if (!isset($rincianData[$idpp])) {
                    $perubahanpak[] = [
                        'idpp' => $idpp,
                        'usulan' => $rincipak['usulan'] ?? '',
                        'koderek108' => $rincipak['koderek108'] ?? '',
                        'uraian108' => $rincipak['uraian108'] ?? '',
                        'volume' => 0,
                        'harga' => 0,
                        'total' => 0,
                        'satuan' => $rincipak['satuan'] ?? '',
                        'volumebaru' => $rincipak['volume'] ?? 0,
                        'hargabaru' => $rincipak['harga'] ?? 0,
                        'totalbaru' => $rincipak['total'] ?? 0,
                        'kode1' => $rincipak['kode1'] ?? '',
                        'kode2' => $rincipak['kode2'] ?? '',
                        'kode3' => $rincipak['kode3'] ?? '',
                        'kode4' => $rincipak['kode4'] ?? '',
                        'kode5' => $rincipak['kode5'] ?? '',
                        'kode6' => $rincipak['kode6'] ?? '',
                        'uraian1' => $rincipak['uraian1'] ?? '',
                        'uraian2' => $rincipak['uraian2'] ?? '',
                        'uraian3' => $rincipak['uraian3'] ?? '',
                        'uraian4' => $rincipak['uraian4'] ?? '',
                        'uraian5' => $rincipak['uraian5'] ?? '',
                        'uraian6' => $rincipak['uraian6'] ?? '',
                    ];
                }
            }
        }

        return [
            'id' => $item['id'],
            'notrans' => $item['notrans'],
            'kodepptk' => $item['kodepptk'],
            'pptk' => $item['pptk'],
            'kodebidang' => $item['kodebidang'],
            'namabidang' => $item['namabidang'],
            'kodekegiatan' => $item['kodekegiatan'],
            'kegiatan' => $item['kegiatan'],
            'capaianprogram' => $item['capaianprogram'],
            'masukan' => $item['masukan'],
            'keluaran' => $item['keluaran'],
            'hasil' => $item['hasil'],
            'targetcapaian' => $item['targetcapaian'],
            'targetkeluaran' => $item['targetkeluaran'],
            'targethasil' => $item['targethasil'],
            'hasilpergeseran' => $item['hasilpergeseran'],
            'perubahanpak' => $perubahanpak,
        ];
    })->all();

        // $alldata = [
        //     'pergeseran' => $combinedData,
        //     'perubahan' => $datapak
        // ];
        return new JsonResponse($finalData);
    }
}
