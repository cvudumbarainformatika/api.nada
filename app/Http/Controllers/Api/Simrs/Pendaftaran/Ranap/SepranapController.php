<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Logx\Logsep;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Ranap\BpjsSpri;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SepranapController extends Controller
{
    public function sepranap()
    {
        $carisepranap = Sepranap::sepranap()->filter(request('noka'))->get();
        return new JsonResponse(['message' => 'OK', $carisepranap], 200);
    }

    public function getRujukanBridgingByNoka(Request $request)
    {
        $request->validate([
            'noka' => 'required'
        ]);

        $cariRujukan = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/Peserta/' . $request->noka);

        return new JsonResponse($cariRujukan, 200);
    }
    public function getPpkRujukan(Request $request)
    {
        $request->validate([
            'param' => 'required',
            'jnsFaskes' => 'required'
        ]);

        $cariRujukan = BridgingbpjsHelper::get_url('vclaim', 'referensi/faskes/' . $request->param.'/'.$request->jnsFaskes);

        return new JsonResponse($cariRujukan, 200);
    }
    public function getDiagnosaBpjs(Request $request)
    {
        $request->validate([
            'param' => 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/diagnosa/' . $request->param);

        return new JsonResponse($data, 200);
    }
    public function getPropinsiBpjs(Request $request)
    {
        // $request->validate([
        //     'param' => 'required',
        // ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/propinsi');

        return new JsonResponse($data, 200);
    }
    public function getKabupatenBpjs(Request $request)
    {
        $request->validate([
            'param' => 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/kabupaten/propinsi/' . $request->param);

        return new JsonResponse($data, 200);
    }
    public function getKecamatanBpjs(Request $request)
    {
        $request->validate([
            'param' => 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/kecamatan/kabupaten/' . $request->param);

        return new JsonResponse($data, 200);
    }
    public function getDpjpBpjs(Request $request)
    {
        $request->validate([
            'jnsPelayanan' => 'required',
            'tglPelayanan' => 'required',
            'kodeSpesialis'=>'required'
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/dokter/pelayanan/' . $request->jnsPelayanan. '/tglPelayanan/' . $request->poli.'/Spesialis/'.$request->kodeSpesialis);

        return new JsonResponse($data, 200);
    }

    public function getListRujukanPeserta(Request $request)
    {
       $pcare = self::rujukanPcare($request);
       $rs=self::rujukanRs($request);
       return new JsonResponse(['pcare'=>$pcare,'rs'=>$rs], 200);
    }

    public static function rujukanPcare($request){

        $request->validate([
            'noka'=>'required',
        ]);
        $data = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/List/Peserta/' . $request->noka);
        $list=[];
        if ($data['metadata']['code'] == '200') {
            $list = $data['result'];
        }

        return $list;
    }
    public static function rujukanRs($request){

        $request->validate([
            'noka'=>'required',
        ]);
        $data = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/RS/List/Peserta/' . $request->noka);
        $list=[];
        if ($data['metadata']['code'] == '200') {
            $list = $data['result'];
        }

        return $list;
    }

    public function getListSpri(Request $request)
    {
        $request->validate([
            'noreg'=> 'required',
        ]);

        $data = BpjsSpri::select('noreg','norm','noSep','kodeDokter','namaDokter','tglRencanaKontrol','noSuratKontrol','noKartu','nama',)
        ->where('noreg', $request->noreg)->whereNull('batal')
        ->orderBy('created_at', 'desc')->get();
        return new JsonResponse($data, 200);
    }


    public function getSuplesi(Request $request)
    {
        $request->validate([
            'noka'=>'required',
        ]);
        $tglPelayanan = Carbon::now()->toDateString();
        $data = BridgingbpjsHelper::get_url('vclaim', 'sep/JasaRaharja/Suplesi/' . $request->noka. '/tglPelayanan/' . $tglPelayanan);
        $list=[];
        if ($data['metadata']['code'] == '200') {
            $list = $data['result'];
        }

        return $list;
    }

    public function getListSpesialistik(Request $request)
    {
       $data = BridgingbpjsHelper::get_url('vclaim', "RencanaKontrol/ListSpesialistik/JnsKontrol/{$request->jnsKontrol}/nomor/{$request->nomor}/TglRencanaKontrol/{$request->tglRencanaKontrol}");
       return $data;
    }

    public function getListDokterBpjs(Request $request)
    {
       $data = BridgingbpjsHelper::get_url('vclaim', "RencanaKontrol/JadwalPraktekDokter/JnsKontrol/{$request->jnsKontrol}/KdPoli/{$request->kodePoli}/TglRencanaKontrol/{$request->tglRencanaKontrol}");
       return $data;
    }

    public function cariSepBpjs(Request $request)
    {
        $request->validate([
            'sep'=> 'required',
        ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $request->sep);
        return $data;
    }

    public function insertSepManual(Request $request)
    {
        $request->validate([
            'noSep'=> 'required',
            'pasien'=> 'required',
        ]);

        // return $request->pasien['noreg'];

        $data = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $request->noSep);
        $code = $data['metadata']['code'];
        if ($code !== '200') {
            return new JsonResponse($data, 200);
        }

        $sepx = $data['result'];

        // update to rs227
        Sepranap::updateOrCreate(
            ['rs1' => $request->pasien['noreg'], 'rs8'=> $sepx->noSep],
            [
                'rs2' => $request->pasien['norm'] ?? '',
                'rs3' => $request->pasien['ruangan'] ?? '',
                'rs4' => $request->pasien['kodesistembayar'] ?? "",
                'rs5' => $sepx->noRujukan ?? '',
                'rs6' => $sepx->tglSep ?? '',
                'rs7' => $sepx->diagnosa ?? '',
                
                'rs9'=> $sepx->catatan ?? '-',
                'rs10'=> 'RSUD MOH SALEH',
                'rs11' =>$sepx->peserta->jenispeserta ?? '',
                'rs12'=> $sepx->tglSep ?? '',
                'rs13'=> $sepx->peserta->noKartu ?? '',
                'rs14' => $sepx->peserta->nama ?? '',
                'rs15' => $sepx->peserta->tglLahir ?? '',
                'rs16' => $sepx->peserta->kelamin ?? '',
                'rs17' => 'Rawat Inap',
                'rs18'=> $sepx->kelasRawat ?? '',
                'rs19'=> '1',
                'laka'=> $sepx->kdStatusKecelakaan ?? '0',
                'lokasilaka' => $sepx->lokasiKejadian->lokasi ?? '',
                'penjaminlaka' => $sepx->penjamin,
                'users' => auth()->user()->pegawai_id,
                // 'notelepon'=> '',
                // 'tgl_entery' => $tgltobpjshttpres,
                'namaasuransicob'=> $sepx->peserta->asuransi,
                'noDpjp'=> $sepx->kontrol->kdDokter ?? '',
                'tgl_kejadian_laka' => $sepx->lokasiKejadian->tglKejadian,
                'keterangan' => $sepx->lokasiKejadian->ketKejadian,
                // 'suplesi' => $request->jaminan->penjamin->suplesi->suplesi,
                // 'nosuplesi' => $request->jaminan->penjamin->suplesi->noSepSuplesi,
                'kdpropinsi' => $sepx->lokasiKejadian->kdProp,
                // 'propinsi',
                'kdkabupaten' => $sepx->lokasiKejadian->kdKab,
                // kabupaten,
                'kdkecamatan' => $sepx->lokasiKejadian->kdKec,
                // kecamatan,
                // kodedokterdpjp,
                // dokterdpjp
            ]
        );

        return $data;
    }

    public function getSepFromBpjs(Request $request)
    {
        $request->validate([
            'noSep'=> 'required',
        ]);

        // return $request->pasien['noreg'];

        $data = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $request->noSep);
        return new JsonResponse($data, 200);
    }


    public function getNorujukanInternal(Request $request)
    {
        DB::select('call generetenorujukan(@nomor)');
        $hcounter = DB::table('rs1')->select('rs285')->first();
        $no = 0;
        $has=null;
        $num = date("y").date("m").date("d").'00000R';
        if ($hcounter) {
			$x=$hcounter->rs285;
            $no = $x+1;

            $panjang = strlen($no);
            for($i=1;$i<=4-$panjang;$i++){$has=$has."0";}
            $num = date("y").date("m").date("d").$has.$no."R";
        }
        return new JsonResponse($num);
    }



    public function create_sep_ranap(Request $request)
    {
        
        $data = [
            "request" => [
                "t_sep" => [
                    "noKartu" => $request->noKartu ? $request->noKartu : "",
                    "tglSep" => $request->tglSep ?? Carbon::now()->toDateString(),
                    "ppkPelayanan" => "1327R001",
                    "jnsPelayanan" => $request->jnsPelayanan ?? "1", //1. Rawat Inap, 2. Rawat Jalan
                    "klsRawat" => [
                        "klsRawatHak" => $request->klsRawat['klsRawatHak'] ?? '',
                        "klsRawatNaik" => $request->klsRawat['klsRawatNaik'] ?? '',
                        "pembiayaan" => $request->klsRawat['pembiayaan'] ?? '',
                        "penanggungJawab" => $request->klsRawat['penanggungJawab'] ?? '',
                    ],
                    "noMR" => $request->noMR ?? "",
                    "rujukan" => [
                        "asalRujukan" => $request->rujukan['asalRujukan'] ?? '',
                        "tglRujukan" => $request->rujukan['tglRujukan'] ?? '',
                        "noRujukan" => $request->rujukan['noRujukan'] ?? '',
                        "ppkRujukan" => $request->rujukan['ppkRujukan'] ?? ''
                    ],
                    "catatan" => $request->catatan ?? '-',
                    "diagAwal" => $request->diagAwal ?? '',
                    "poli" => ["tujuan" => $request->poli['tujuan'] ?? '', "eksekutif" => $request->poli['eksekutif'] ?? '0'],
                    "cob" => [
                        "cob" => $request->cob['cob'] ?? '0',
                    ],
                    "katarak" => [
                        "katarak" => $request->katarak['katarak'] ?? '0',
                    ],
                    "jaminan" => [
                        "lakaLantas" => $request->jaminan['lakaLantas'] ?? '0',
                        "noLP" => $request->jaminan['noLP'] ?? '',
                        "penjamin" => [
                            "tglKejadian" => $request->jaminan['penjamin']['tglKejadian'] ?? '',
                            "keterangan" => $request->jaminan['penjamin']['keterangan'] ?? '',
                            "suplesi" => [
                                "suplesi" => $request->jaminan['penjamin']['suplesi']['suplesi'] ?? '0',
                                "noSepSuplesi" => $request->jaminan['penjamin']['suplesi']['noSepSuplesi'] ?? '',
                                "lokasiLaka" => [
                                    "kdPropinsi" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdPropinsi'] ?? '',
                                    "kdKabupaten" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKabupaten'] ?? '',
                                    "kdKecamatan" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKecamatan'] ?? '',
                                ],
                            ],
                        ],
                    ],
                    "tujuanKunj" => $request->tujuanKunj ?? '0',
                    "flagProcedure" => $request->flagProcedure ?? '0',
                    "kdPenunjang" => $request->kdPenunjang ?? '',
                    "assesmentPel" => $request->assesmentPel ?? '',
                    "skdp" => [
                        "noSurat" => $request->skdp['noSurat'] ?? '', // ini dari SPRI
                        "kodeDPJP" => $request->skdp['kodeDPJP'] ?? '',
                    ],
                    "dpjpLayan" => $request->dpjpLayan ?? '', // untuk RANAP dikosongi
                    "noTelp" => $request->noTelp ?? '',
                    "user" => $request->user ?? '-',
                ],
            ],
        ];

        // return new JsonResponse($data);

        $tgltobpjshttpres = DateHelper::getDateTime();
        $createsep = BridgingbpjsHelper::post_url(
            'vclaim',
            'SEP/2.0/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg === null ? '' : $request->noreg,
                'request' => $data,
                'respon' => $createsep,
                'url' => '/SEP/2.0/insert',
                'tgl' => $tgltobpjshttpres
            ]
        );

        // simpan ke rs227

        $bpjs = $createsep['metadata']['code'];
        if ($bpjs === 200 || $bpjs === '200') {
            $sepx = $createsep['response']->sep;
            $nosep = $sepx->noSep;
            // $dinsos = $sepx->informasi->dinsos;
            // $prolanisPRB = $sepx->informasi->prolanisPRB;
            // $noSKTM = $sepx->informasi->noSKTM;
            Sepranap::firstOrCreate(
                ['rs1' => $request->noreg],
                [
                    'rs2' => $request->noMR ?? "",
                    'rs3' => $request->sepRanap['ruang'] ?? "",
                    'rs4' => $request->sepRanap['kodesistembayar'] ?? "",
                    'rs5' => $request->rujukan['noRujukan'] ?? '',
                    'rs6' => $request->rujukan['tglRujukan'] ?? '',
                    'rs7' => $request->sepRanap['diagnosa'] ?? '',
                    'rs8'=> $nosep,
                    'rs9'=> $request->catatan ?? '-',
                    'rs10'=> $request->rujukan['ppkRujukan'] ?? '',
                    'rs11' =>$request->sepRanap['jenispeserta'] ?? '',
                    'rs12'=> $tgltobpjshttpres ?? '',
                    'rs13'=> $request->noKartu ?? '',
                    'rs14' => $request->sepRanap['nama'] ?? '',
                    'rs15' => $request->sepRanap['tglLahir'] ?? '',
                    'rs16' => $request->sepRanap['jeniskelamin']==='Laki-Laki' ? 'L' : 'P', 
                    'rs17' => 'Rawat Inap',
                    'rs18'=> $request->sepRanap['hakKelas'] ?? '',
                    'rs19'=> '1',
                    'laka'=> $request->jaminan['lakaLantas'] ?? '0',
                    'lokasilaka' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKabupaten'] ?? '',
                    'penjaminlaka' => $request->jaminan['penjamin']['suplesi']['noSepSuplesi'] ?? '',
                    'users' => auth()->user()->pegawai_id,
                    'notelepon'=> $request->noTelp ?? '',
                    'tgl_entery' => $tgltobpjshttpres,
                    'namaasuransicob'=> $request->sepRanap['namaAsuransiCob'] ?? '',
                    'noDpjp'=> $request->skdp['kodeDPJP'] ?? '',
                    'tgl_kejadian_laka' => $request->jaminan['penjamin']['tglKejadian'] ?? '',
                    'keterangan' => $request->jaminan['penjamin']['keterangan'] ?? '',
                    'suplesi' => $request->jaminan['penjamin']['suplesi']['suplesi'] ?? '',
                    'nosuplesi' => $request->jaminan['penjamin']['suplesi']['noSepSuplesi'] ?? '',
                    'kdpropinsi' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdPropinsi'] ?? '',
                    // 'propinsi',
                    'kdkabupaten' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKabupaten'] ?? '',
                    // kabupaten,
                    'kdkecamatan' => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKecamatan'] ?? '',
                    // kecamatan,
                    // kodedokterdpjp,
		            // dokterdpjp
                ]
            );
        }


       return $createsep;
    }

    public function update_sep_ranap(Request $request)
    {
        $request->validate([
            'noSep' => 'required',
        ]);

        // return $request->all();
        
        $data = [
            "request" => [
                "t_sep" => [
                    "noSep" => $request->noSep,
                    "klsRawat" => [
                        "klsRawatHak" => $request->klsRawat['klsRawatHak'] ?? '',
                        "klsRawatNaik" => $request->klsRawat['klsRawatNaik'] ?? '',
                        "pembiayaan" => $request->klsRawat['pembiayaan'] ?? '',
                        "penanggungJawab" => $request->klsRawat['penanggungJawab'] ?? ''
                    ],
                    "noMR" => $request->noMR ?? '-',
                    "catatan" => $request->catatan ?? '-',
                    "diagAwal" => $request->diagAwal ?? '',
                    "poli" => ["tujuan" => $request->poli['tujuan'] ?? '', "eksekutif" => $request->poli['eksekutif'] ?? '0'],
                    "cob" => ["cob" => $request->cob['cob'] ?? '0',],
                    "katarak" => ["katarak" => $request->katarak['katarak'] ?? '0',],
                    "jaminan" => [
                        "lakaLantas" => $request->jaminan['lakaLantas'] ?? '0',
                        "penjamin" => [
                            "tglKejadian" => $request->jaminan['penjamin']['tglKejadian'] ?? '',
                            "keterangan" => $request->jaminan['penjamin']['keterangan'] ?? '',
                            "suplesi" => [
                                "suplesi" => $request->jaminan['penjamin']['suplesi']['suplesi'] ?? '0',
                                "noSepSuplesi" => $request->jaminan['penjamin']['suplesi']['noSepSuplesi'] ?? '',
                                "lokasiLaka" => [
                                    "kdPropinsi" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdPropinsi'] ?? '',
                                    "kdKabupaten" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKabupaten'] ?? '',
                                    "kdKecamatan" => $request->jaminan['penjamin']['suplesi']['lokasiLaka']['kdKecamatan'] ?? '',
                                ],
                            ],
                        ],
                    ],
                    "dpjpLayan" => $request->dpjpLayan ?? '',
                    "noTelp" => $request->noTelp ?? '',
                    "user" => $request->user ?? '-',
                ],
            ],
        ];

        // return $data;
        $tgltobpjshttpres = DateHelper::getDateTime();
        $updateSep = BridgingbpjsHelper::put_url(
            'vclaim',
            'SEP/2.0/update',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'PUT',
                'noreg' => $request->noreg === null ? '' : $request->noreg,
                'request' => $data,
                'respon' => $updateSep,
                'url' => '/SEP/2.0/update',
                'tgl' => $tgltobpjshttpres
            ]
        );

        // update ke rs227
        $bpjs = $updateSep['metadata']['code'];
        if ($bpjs === 200 || $bpjs === '200') {
            Sepranap::where('rs1', $request->noreg)->update(
                [
                    'rs7' => $request->sepRanap['diagnosa'] ?? '',
                    'users' => auth()->user()->pegawai_id,
                    'kodedokterdpjp'=> $request->dpjpLayan ?? '',
                    'dokterdpjp'=> $request->sepRanap['namaDpjp'] ?? ''
                ]
            );
        }

        return $updateSep;
        
    }

    public function delete_sep(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'noSep' => 'required',
        ]);

        $data = [
            "request" => [
                "t_sep" => ["noSep" => $request->noSep, "user" => auth()->user()->nama],
            ],
        ];

        $tgltobpjshttpres = DateHelper::getDateTime();
        $deleteSep = BridgingbpjsHelper::delete_url(
            'vclaim',
            'SEP/2.0/delete',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'delete',
                'noreg' => $request->noreg === null ? '' : $request->noreg,
                'request' => $data,
                'respon' => $deleteSep,
                'url' => '/SEP/2.0/delete',
                'tgl' => $tgltobpjshttpres
            ]
        );


        $bpjs = $deleteSep['metadata']['code'];

        if ($bpjs === 200 || $bpjs === '200') {
            // delete rs227
            Sepranap::where('rs1', $request->noreg)->delete();
            // insert ke log sep
            Logsep::create([
                'nosep' => $request->noSep,
                'tgl' => $tgltobpjshttpres,
                'users' => auth()->user()->pegawai_id
            ]);
        }

        


        return $deleteSep;
    }


    public function create_spri_ranap(Request $request)
    {
    

        // CREATE SPRI
        if ($request->noSuratKontrol === null || $request->noSuratKontrol === '') {
            $payload = [
                "request" => [
                    "noKartu" => $request->pasien['noka'] ?? "",
                    "kodeDokter" => $request->dokter["kodeDokter"] ?? "",
                    "poliKontrol" => $request->spesialis["kodePoli"] ?? "",
                    "tglRencanaKontrol" => $request->tglRawatInap ?? "",
                    "user" => auth()->user()->nama,
                ],
            ];
    
            $createSpri = BridgingbpjsHelper::post_url(
                'vclaim',
                'RencanaKontrol/InsertSPRI',
                $payload
            );
    
            $tgltobpjshttpres = DateHelper::getDateTime();
            Bpjs_http_respon::create(
                [
                    'method' => 'POST',
                    'noreg' => $request->pasien['noreg'] === null ? '' : $request->pasien['noreg'],
                    'request' => $payload,
                    'respon' => $createSpri,
                    'url' => '/RencanaKontrol/InsertSPRI',
                    'tgl' => $tgltobpjshttpres
                ]
            );
    
    
            $bpjs = $createSpri['metadata']['code'];
            if ($bpjs === 200 || $bpjs === '200') {
                $sprix = $createSpri['response'];
    
                BpjsSpri::updateOrCreate(
                    ['noreg' => $request->pasien['noreg'], 'noSuratKontrol'=> $sprix->noSPRI],
                    [
                        'norm' => $request->pasien['norm'],
                        'kodeDokter' => $request->dokter['kodeDokter'],
                        'poliKontrol' => $request->spesialis['kodePoli'],
                        'tglRencanaKontrol' => $request->tglRawatInap,
                        'namaDokter' => $request->dokter['namaDokter'],
                        'noKartu' => $request->pasien['noka'],
                        'nama' => $request->pasien['nama'],
                        'kelamin' => $request->pasien['kelamin'],
                        'tglLahir' => $request->pasien['tgllahir'],
                        'user_id' => auth()->user()->pegawai_id,
                    ]
                );
            }
           return $createSpri;

        } else { // UPDATE SPRI

            $request->validate([
                'noSuratKontrol' => 'required',
            ]);

            $payload = [
                "request" => [
                    "noSPRI" => $request->noSuratKontrol,
                    "kodeDokter" => $request->dokter["kodeDokter"] ?? "",
                    "poliKontrol" => $request->spesialis["kodePoli"] ?? "",
                    "tglRencanaKontrol" => $request->tglRawatInap ?? "",
                    "user" => auth()->user()->nama,
                ],
            ];

            $updateSpri = BridgingbpjsHelper::put_url(
                'vclaim',
                'RencanaKontrol/UpdateSPRI',
                $payload
            );
    
            $tgltobpjshttpres = DateHelper::getDateTime();
            Bpjs_http_respon::create(
                [
                    'method' => 'PUT',
                    'noreg' => $request->pasien['noreg'] === null ? '' : $request->pasien['noreg'],
                    'request' => $payload,
                    'respon' => $updateSpri,
                    'url' => '/RencanaKontrol/UpdateSPRI',
                    'tgl' => $tgltobpjshttpres
                ]
            );

            $bpjs = $updateSpri['metadata']['code'];
            if ($bpjs === 200 || $bpjs === '200') {
                $sprix = $updateSpri['result'];
    
                BpjsSpri::updateOrCreate(
                    ['noreg' => $request->pasien['noreg'], 'noSuratKontrol'=> $sprix->noSPRI],
                    [
                        'norm' => $request->pasien['norm'],
                        'kodeDokter' => $request->dokter['kodeDokter'],
                        'poliKontrol' => $request->spesialis['kodePoli'],
                        'tglRencanaKontrol' => $request->tglRawatInap,
                        'namaDokter' => $request->dokter['namaDokter'],
                        'noKartu' => $request->pasien['noka'],
                        'nama' => $request->pasien['nama'],
                        'kelamin' => $request->pasien['kelamin'],
                        'tglLahir' => $request->pasien['tgllahir'],
                        'user_id' => auth()->user()->pegawai_id,
                    ]
                );
            }
           return $updateSpri;
        }
        
    }


    public function delete_spri_ranap(Request $request)
    {

        $request->validate([
            'noSuratKontrol' => 'required'
        ]);

        $payload = [
            "request" => [
                "t_suratkontrol" => [
                    "noSuratKontrol" => $request->noSuratKontrol,
                    "user" => auth()->user()->nama,
                ],
            ],
        ];

        $deleteSpri = BridgingbpjsHelper::delete_url(
            'vclaim',
            'RencanaKontrol/Delete',
            $payload
        );

        $tgltobpjshttpres = DateHelper::getDateTime();
        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->pasien['noreg'] === null ? '' : $request->pasien['noreg'],
                'request' => $payload,
                'respon' => $deleteSpri,
                'url' => '/RencanaKontrol/Delete',
                'tgl' => $tgltobpjshttpres
            ]
        );


        $bpjs = $deleteSpri['metadata']['code'];
        if ($bpjs === 200 || $bpjs === '200') {
            BpjsSpri::where('noSuratKontrol', $request->noSuratKontrol)->delete();
        }

        return $deleteSpri;
    }



}
