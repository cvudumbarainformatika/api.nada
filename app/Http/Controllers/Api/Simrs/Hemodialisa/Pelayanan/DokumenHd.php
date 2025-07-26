<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DokumenHd extends Controller
{
    public function resume()
    {
        $noreg = request('noreg');
        $rajal = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs23_meta.kd_jeniskasus',
            'memodiagnosadokter.diagnosa as memodiagnosa',
        )->where('rs1', $noreg)
            ->leftjoin('rs23_meta', 'rs23_meta.noreg', 'rs17.rs1')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs17.rs1') // memo
            ->first();
        $ranap = Kunjunganranap::select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23_meta.kd_jeniskasus',
            'memodiagnosadokter.diagnosa as memodiagnosa',
        )->where('rs1', $noreg)
            ->leftjoin('rs23_meta', 'rs23_meta.noreg', 'rs23.rs1')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs23.rs1') // memo
            ->first();
        $data = $ranap ?? $rajal;
        $data->load([
            'newapotekrajal' => function ($q) {
                $q->with([
                    'dokter:nama,kdpegsimrs',
                    'permintaanresep.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                    'permintaanracikan.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                    'sistembayar'
                ])
                    ->where('ruangan', '!=', 'POL014')
                    ->orderBy('id', 'DESC');
            },
            'rs239_implementasi',
            'diagnosa', // ini berhubungan dengan resep
            'anamnesis' => function ($q) {
                $q->select([
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
                    ->with([
                        'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                        'keluhannyeri',
                        'skreeninggizi',
                        'neonatal',
                        'pediatrik',
                        'kebidanan'
                    ])

                    ->groupBy('rs209.id');
            },
            'pemeriksaan' => function ($q) {
                $q->select([
                    'rs253.id',
                    'rs253.rs1',
                    'rs253.rs1 as noreg',
                    'rs253.rs2 as norm',
                    'rs253.rs3 as tgl',
                    'rs253.rs4 as ruang',
                    'rs253.pernapasan as pernapasanigd',
                    'rs253.nadi as nadiigd',
                    'rs253.tensi as tensiigd',
                    'rs253.beratbadan',
                    'rs253.tinggibadan',
                    'rs253.kdruang',
                    'rs253.user',
                    'rs253.awal',
                    'rs253.rs5',
                    'rs253.rs6',
                    'rs253.rs7',
                    'rs253.rs8',
                    'rs253.rs9',
                    'rs253.rs10',
                    'rs253.rs11',
                    'rs253.rs12',
                    'rs253.rs13',
                    'rs253.sax',
                    'rs253.srec',

                    'sambung.keadaanUmum',
                    'sambung.bb',
                    'sambung.tb',
                    'sambung.nadi',
                    'sambung.suhu',
                    'sambung.sistole',
                    'sambung.diastole',
                    'sambung.pernapasan',
                    'sambung.spo',
                    'sambung.tkKesadaran',
                    'sambung.tkKesadaranKet',
                    'sambung.sosial',
                    'sambung.spiritual',
                    'sambung.statusPsikologis',
                    'sambung.ansuransi',
                    'sambung.edukasi',
                    'sambung.ketEdukasi',
                    'sambung.penyebabSakit',
                    'sambung.komunikasi',
                    'sambung.makananPokok',
                    'sambung.makananPokokLain',
                    'sambung.pantanganMkanan',

                    'pegawai.nama as petugas',
                    'pegawai.kdgroupnakes as nakes',
                ])
                    ->leftJoin('rs253_sambung as sambung', 'rs253.id', '=', 'sambung.rs253_id')
                    ->leftJoin('kepegx.pegawai as pegawai', 'rs253.user', '=', 'pegawai.kdpegsimrs')
                    //    ->where('rs253.rs1','=', $noreg)
                    ->with([
                        'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                        'neonatal',
                        'pediatrik',
                        'kebidanan',
                        //  'penilaian'
                    ])
                    ->groupBy('rs253.id');
            },
            'penilaian' => function ($q) {
                $q->select([
                    'id',
                    'rs1',
                    'rs1 as noreg',
                    'rs2 as norm',
                    'rs3 as tgl',
                    'barthel',
                    'norton',
                    'humpty_dumpty',
                    'morse_fall',
                    'ontario',
                    'user',
                    'kdruang',
                    'awal',
                    'group_nakes'
                ])
                    ->where('kdruang', '!=', 'POL014')
                    ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes']);
            },
            'diagnosamedis' => function ($q) {
                $q->with('masterdiagnosa');
            },
            'diagnosakeperawatan' => function ($q) {
                $q->with('intervensi', 'intervensi.masterintervensi')
                    ->where('kdruang', '!=', 'POL014')
                    ->orderBy('id', 'DESC');
            },
            'diagnosakebidanan' => function ($q) {
                $q->with('intervensi', 'intervensi.masterintervensi')
                    ->where('kdruang', '!=', 'POL014')
                    ->orderBy('id', 'DESC');
            },
            'diagnosagizi' => function ($q) {
                $q->with('intervensi', 'intervensi.masterintervensi')
                    ->where('kdruang', '!=', 'POL014')
                    ->orderBy('id', 'DESC');
            },
            'tindakan' => function ($q) {
                $q->select(
                    'id',
                    'rs1',
                    'rs2',
                    'rs4',
                    'rs1 as noreg',
                    'rs2 as nota',
                    'rs3',

                    'rs4',
                    'rs5',
                    'rs6',
                    'rs7',
                    'rs8',
                    'rs9',
                    'rs13',
                    'rs14',
                    'rs20',
                    'rs22',
                    'rs23',
                    'rs24',
                )
                    ->where('rs22', '!=', 'POL014')
                    ->with(['mastertindakan:rs1,rs2', 'sambungan:rs73_id,ket'])
                    ->orderBy('id', 'DESC');
            },
            'intradialitik.user:nama,kdpegsimrs',
            'pengkajian',

            'laborats' => function ($q) {

                $q->with('details.pemeriksaanlab')->orderBy('id', 'DESC')
                    ->where('unit_pengirim', '=', 'PEN005');
            },
            'laboratold' => function ($t) {
                $t->with('pemeriksaanlab')
                    ->orderBy('id', 'DESC');
            },
            'radiologi' => function ($q) {
                $q->orderBy('id', 'DESC')
                    ->where('rs10', '=', 'PEN005');
            },
            'hasilradiologi' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'bankdarah' => function ($q) {
                $q->orderBy('id', 'DESC')
                    ->where('rs11', '=', 'PEN005');
            },
            'konsultasi' => function ($q) {
                $q->where('kdruang', '=', 'PEN005')
                    ->with([
                        'tarif:id,rs1,rs3,rs4,rs5,rs6,rs7,rs8,rs9,rs10',
                        'nakesminta:kdpegsimrs,nama,kdgroupnakes,statusspesialis',
                    ])
                    ->orderBy('id', 'DESC'); // ini updatean baru
            },
            'edukasi' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'dokumenluar' => function ($neo) {
                $neo->with(['pegawai:id,nama'])
                    ->orderBy('id', 'DESC');
            },
        ]);

        return new JsonResponse($data);
    }
}
