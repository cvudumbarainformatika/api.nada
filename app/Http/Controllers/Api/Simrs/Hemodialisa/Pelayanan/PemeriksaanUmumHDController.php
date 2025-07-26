<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanKebidanan;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanNeonatal;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanPediatrik;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanSambung;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemeriksaanUmumHDController extends Controller
{

    public function list()
    {

        $data = self::getdata(request('noreg'));
        return new JsonResponse($data);
    }

    public static function getdata($noreg)
    {
        // $akun = auth()->user()->pegawai_id;
        // $nakes = Petugas::select('kdgroupnakes')->find($akun)->kdgroupnakes;
        // $data = PemeriksaanUmum::select('rs253.*')
        $data = PemeriksaanUmum::select([
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
            ->where('rs253.rs1', '=', $noreg)
            ->with([
                'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                'neonatal',
                'pediatrik',
                'kebidanan',
                //  'penilaian'
            ])

            ->where('rs253.kdruang', 'PEN005')
            ->groupBy('rs253.id')
            ->get();

        return $data;
    }
    public static function getDataAwal($noreg)
    {
        // $akun = auth()->user()->pegawai_id;
        // $nakes = Petugas::select('kdgroupnakes')->find($akun)->kdgroupnakes;
        // $data = PemeriksaanUmum::select('rs253.*')
        $data = PemeriksaanUmum::select([
            'rs253.id',
            'rs253.rs1',
            'rs253.rs2',
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
            ->where('rs253.rs2', '=', $noreg)
            ->with([
                'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                'neonatal',
                'pediatrik',
                'kebidanan',
                //  'penilaian'
            ])

            ->where('rs253.awal', '1')
            ->where('rs253.kdruang', 'PEN005')
            ->groupBy('rs253.id')
            ->get();

        return $data;
    }

    public function simpan(Request $request)
    {
        $data = self::store($request);
        return new JsonResponse($data);
    }

    public static function store($request)
    {

        // return $request->all();

        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;


        DB::beginTransaction();
        try {
            if ($request->id !== null) {
                $hasil = PemeriksaanUmum::where('id', $request->id)->update(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->norm,
                        'rs3' => date('Y-m-d H:i:s'),
                        'rs4' => $request->ruangan ?? '',

                        'pernapasan' => $request->form['pernapasan'] ?? '',
                        'nadi' => $request->form['nadi'] ?? '',
                        'tensi' =>  $request->form['sistole'] . '/' . $request->form['diastole'],
                        'beratbadan' => $request->form['bb'],
                        'tinggibadan' => $request->form['tb'],

                        'kdruang' => 'PEN005',
                        'awal' => $request->awal ?? null,
                        'user'  => $kdpegsimrs,

                        // sementara
                        'rs5' => $request->form['rs5'] ?? '',
                        'rs6' => $request->form['rs6'] ?? '',
                        'rs7' => $request->form['rs7'] ?? '',
                        'rs8' => $request->form['rs8'] ?? '',
                        'rs9' => $request->form['rs9'] ?? '',
                        'rs10' => $request->form['rs10'] ?? '',
                        'rs11' => $request->form['rs11'] ?? '',
                        'rs12' => $request->form['rs12'] ?? '',
                        'rs13' => $request->form['rs13'] ?? ''

                    ]
                );
                if ($hasil === 1) {
                    $simpan = PemeriksaanUmum::where('id', $request->id)->first();
                } else {
                    $simpan = null;
                }
            } else {
                $simpan = PemeriksaanUmum::create(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->norm,
                        'rs3' => date('Y-m-d H:i:s'),
                        'rs4' => $request->ruangan ?? '',

                        'pernapasan' => $request->form['pernapasan'] ?? '',
                        'nadi' => $request->form['nadi'] ?? '',
                        'tensi' =>  $request->form['sistole'] . '/' . $request->form['diastole'],
                        'beratbadan' => $request->form['bb'],
                        'tinggibadan' => $request->form['tb'],

                        'kdruang' => 'PEN005',
                        'awal' => $request->awal ?? null,
                        'user'  => $kdpegsimrs,

                        // sementara
                        'rs5' => $request->form['rs5'] ?? '',
                        'rs6' => $request->form['rs6'] ?? '',
                        'rs7' => $request->form['rs7'] ?? '',
                        'rs8' => $request->form['rs8'] ?? '',
                        'rs9' => $request->form['rs9'] ?? '',
                        'rs10' => $request->form['rs10'] ?? '',
                        'rs11' => $request->form['rs11'] ?? '',
                        'rs12' => $request->form['rs12'] ?? '',
                        'rs13' => $request->form['rs13'] ?? ''
                    ]
                );
            }

            // save sambungan rs253
            PemeriksaanSambung::updateOrCreate(
                ['rs253_id' => $simpan->id],
                [
                    'keadaanUmum' => $request->form['keadaanUmum'] ?? '',
                    'bb' => $request->form['bb'],
                    'tb' => $request->form['tb'],
                    'nadi' => $request->form['nadi'],
                    'suhu' => $request->form['suhu'],
                    'sistole' => $request->form['sistole'],
                    'diastole' => $request->form['diastole'],
                    'pernapasan' => $request->form['pernapasan'],
                    'spo' => $request->form['spo'],
                    'tkKesadaran' => $request->form['tkKesadaran'],
                    'tkKesadaranKet' => $request->form['tkKesadaranKet'],
                    'sosial' => $request->form['sosial'],
                    'spiritual' => $request->form['spiritual'],
                    'statusPsikologis' => $request->form['statusPsikologis'],
                    'ansuransi' => $request->form['ansuransi'],
                    'edukasi' => $request->form['edukasi'],
                    'ketEdukasi' => $request->form['ketEdukasi'],
                    'penyebabSakit' => $request->form['penyebabSakit'],
                    'komunikasi' => $request->form['komunikasi'],
                    'makananPokok' => $request->form['makananPokok'],
                    'makananPokokLain' => $request->form['makananPokokLain'],
                    'pantanganMkanan' => $request->form['pantanganMkanan'],
                ]
            );

            // save kebidanan
            if ($request->formKebidanan !== null) {
                PemeriksaanKebidanan::updateOrCreate(
                    ['rs253_id' => $simpan->id],
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'nyeri'  => $request->formKebidanan['nyeri'],
                        'lochea'  => $request->formKebidanan['lochea'],
                        'proteinUrin'  => $request->formKebidanan['proteinUrin'],
                        'mata'  => $request->formKebidanan['mata'],
                        'leher'  => $request->formKebidanan['leher'],
                        'dada'  => $request->formKebidanan['dada'],
                        'putingMenonjol'  => $request->formKebidanan['putingMenonjol'],
                        'hiperpigmentasi'  => $request->formKebidanan['hiperpigmentasi'],
                        'kolostrum'  => $request->formKebidanan['kolostrum'],
                        'konsistensiPayudara'  => $request->formKebidanan['konsistensiPayudara'],
                        'nyeriTekan'  => $request->formKebidanan['nyeriTekan'],
                        'benjolan'  => $request->formKebidanan['benjolan'],
                        'abdomen'  => $request->formKebidanan['abdomen'],
                        'anoGenital'  => $request->formKebidanan['anoGenital'],
                        'ekstremitasTungkai'  => $request->formKebidanan['ekstremitasTungkai'],
                        'hmlInspeksi'  => $request->formKebidanan['hmlInspeksi'],
                        'hmlTfuPuka'  => $request->formKebidanan['hmlTfuPuka'],
                        'hmlTfuPuki'  => $request->formKebidanan['hmlTfuPuki'],
                        'hmlTfuPresentasi'  => $request->formKebidanan['hmlTfuPresentasi'],
                        'hmlNyeri'  => $request->formKebidanan['hmlNyeri'],
                        'hmlOsborn'  => $request->formKebidanan['hmlOsborn'],
                        'hmlCekung'  => $request->formKebidanan['hmlCekung'],
                        'hmlAusDenyut'  => $request->formKebidanan['hmlAusDenyut'],
                        'hmlAusTeratur'  => $request->formKebidanan['hmlAusTeratur'],
                        'hmlHisFrekuensi'  => $request->formKebidanan['hmlHisFrekuensi'],
                        'hmlHisIntensitas'  => $request->formKebidanan['hmlHisIntensitas'],
                        'hmlVgnBentuk'  => $request->formKebidanan['hmlVgnBentuk'],
                        'hmlVgnJml'  => $request->formKebidanan['hmlVgnJml'],
                        'hmlVgnKtuban'  => $request->formKebidanan['hmlVgnKtuban'],
                        'hmlVgnToucher'  => $request->formKebidanan['hmlVgnToucher'],
                        'nfsTfu'  => $request->formKebidanan['nfsTfu'],
                        'nfsUterus'  => $request->formKebidanan['nfsUterus'],
                        'nfsVgnBentuk'  => $request->formKebidanan['nfsVgnBentuk'],
                        'nfsVgnJml'  => $request->formKebidanan['nfsVgnJml'],
                        'nfsVgnLochea'  => $request->formKebidanan['nfsVgnLochea'],
                        'nfsVgnLuka'  => $request->formKebidanan['nfsVgnLuka'],
                        'nfsVgnDrjLuka'  => $request->formKebidanan['nfsVgnDrjLuka'],
                        'nfsVgnLukaPost'  => $request->formKebidanan['nfsVgnLukaPost'],
                        'gynecologiPalpasi'  => $request->formKebidanan['gynecologiPalpasi'],
                        'gynecologiInsVgn'  => $request->formKebidanan['gynecologiInsVgn'],
                        'gynecologiInsPortio'  => $request->formKebidanan['gynecologiInsPortio'],
                        'gynecologiInsVgnToucher'  => $request->formKebidanan['gynecologiInsVgnToucher'],
                        'user_input' => $kdpegsimrs,
                        'group_nakes' => $user->kdgroupnakes

                    ]
                );
            } else {
                PemeriksaanKebidanan::where('rs253_id', $simpan->id)->delete();
            }

            // save neonatal
            if ($request->formNeonatal !== null) {
                PemeriksaanNeonatal::updateOrCreate(
                    ['rs253_id' => $simpan->id],
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,

                        'lila'  => $request->formNeonatal['lila'],
                        'lida'  => $request->formNeonatal['lida'],
                        'lirut'  => $request->formNeonatal['lirut'],
                        'grkBayi'  => $request->formNeonatal['grkBayi'],
                        'uub'  => $request->formNeonatal['uub'],
                        'kejang'  => $request->formNeonatal['kejang'],
                        'refleks'  => $request->formNeonatal['refleks'],
                        'tngsBayi'  => $request->formNeonatal['tngsBayi'],
                        'pssMata'  => $request->formNeonatal['pssMata'],
                        'bsrPupil'  => $request->formNeonatal['bsrPupil'],
                        'klpkMata'  => $request->formNeonatal['klpkMata'],
                        'konjungtiva'  => $request->formNeonatal['konjungtiva'],
                        'sklera'  => $request->formNeonatal['sklera'],
                        'pendengaran'  => $request->formNeonatal['pendengaran'],
                        'penciuman'  => $request->formNeonatal['penciuman'],
                        'warnaKlt'  => $request->formNeonatal['warnaKlt'],
                        'denyutNadi'  => $request->formNeonatal['denyutNadi'],
                        'sirkulasi'  => $request->formNeonatal['sirkulasi'],
                        'pulsasi'  => $request->formNeonatal['pulsasi'],
                        'polaNafas'  => $request->formNeonatal['polaNafas'],
                        'jnsPernafasan'  => $request->formNeonatal['jnsPernafasan'],
                        'irmNapas'  => $request->formNeonatal['irmNapas'],
                        'retraksi'  => $request->formNeonatal['retraksi'],
                        'airEntri'  => $request->formNeonatal['airEntri'],
                        'merintih'  => $request->formNeonatal['merintih'],
                        'suaraNapas'  => $request->formNeonatal['suaraNapas'],
                        'mulut'  => $request->formNeonatal['mulut'],
                        'lidah'  => $request->formNeonatal['lidah'],
                        'oesofagus'  => $request->formNeonatal['oesofagus'],
                        'abdomen'  => $request->formNeonatal['abdomen'],
                        'bab'  => $request->formNeonatal['bab'],
                        'warnaBab'  => $request->formNeonatal['warnaBab'],
                        'warnaUrine'  => $request->formNeonatal['warnaUrine'],
                        'bak'  => $request->formNeonatal['bak'],
                        'laki'  => $request->formNeonatal['laki'],
                        'perempuan'  => $request->formNeonatal['perempuan'],
                        'vernicKasesosa'  => $request->formNeonatal['vernicKasesosa'],
                        'lanugo'  => $request->formNeonatal['lanugo'],
                        'warnaIntegument'  => $request->formNeonatal['warnaIntegument'],
                        'turgor'  => $request->formNeonatal['turgor'],
                        'kulit'  => $request->formNeonatal['kulit'],
                        'lengan'  => $request->formNeonatal['lengan'],
                        'tungkai'  => $request->formNeonatal['tungkai'],
                        'rekoilTelinga'  => $request->formNeonatal['rekoilTelinga'],
                        'grsTlpkKaki'  => $request->formNeonatal['grsTlpkKaki'],
                        'apgarScores'  => $request->formNeonatal['apgarScores'],
                        'apgarScore'  => $request->formNeonatal['apgarScore'],
                        'apgarKet'  => $request->formNeonatal['apgarKet'],

                        'user_input' => $kdpegsimrs,
                        'group_nakes' => $user->kdgroupnakes

                    ]
                );
            } else {
                PemeriksaanNeonatal::where('rs253_id', $simpan->id)->delete();
            }

            // save pediatri
            if ($request->formPediatrik !== null) {
                PemeriksaanPediatrik::updateOrCreate(
                    ['rs253_id' => $simpan->id],
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,

                        'lila'  => $request->formPediatrik['lila'],
                        'lida'  => $request->formPediatrik['lida'],
                        'lirut'  => $request->formPediatrik['lirut'],
                        'lilengtas'  => $request->formPediatrik['lilengtas'],
                        'glasgow'  => $request->formPediatrik['glasgow'],
                        'glasgowSkor'  => $request->formPediatrik['glasgowSkor'],
                        'glasgowKet'  => $request->formPediatrik['glasgowKet'],

                        // ini baru saya lupa
                        'bbi' => $request->formPediatrik['bbi'] ?? null,
                        'bmi' => $request->formPediatrik['bmi'] ?? null,
                        'statusGizi' => $request->formPediatrik['statusGizi'] ?? null,
                        'kesimpulan' => $request->formPediatrik['kesimpulan'] ?? null,

                        'user_input' => $kdpegsimrs,
                        'group_nakes' => $user->kdgroupnakes
                    ]
                );
            } else {
                PemeriksaanPediatrik::where('rs253_id', $simpan->id)->delete();
            }





            DB::commit();
            // return response()->json([
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
                'idPemeriksaan' => $simpan->id,
                'result' => $result,
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
}
