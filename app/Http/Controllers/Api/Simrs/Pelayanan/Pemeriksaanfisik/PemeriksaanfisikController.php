<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik_paru;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisikdetail;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisiksubdetail;
use App\Models\Simrs\Pemeriksaanfisik\Simpangambarpemeriksaanfisik;
use App\Models\Simrs\PemeriksaanRMkhusus\Polimata;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// use Mavinoo\Batch\Batch;
use Mavinoo\Batch\BatchFacade as Batch;

class PemeriksaanfisikController extends Controller
{
    public function simpan(Request $request)
    {

        // return $request->all();
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;

        $noreg = $request->noreg;
        $norm = $request->norm;

        $form = [
            'rs1' => $noreg,
            'rs2' => $norm,
            'rs3' => date('Y-m-d H:i:s'),
            'rs4' => $request->denyutjantung,
            'tingkatkesadaran' => $request->tingkatkesadaran,
            'pernapasan' => $request->pernapasan,
            'sistole' => $request->sistole,
            'diastole' => $request->diastole,
            'suhutubuh' => $request->suhutubuh,
            'statuspsikologis' => $request->statuspsikologis,
            'sosialekonomi' => $request->sosialekonomi,
            'spiritual' => $request->spiritual,
            'user'  => $kdpegsimrs,
            'ruangan' => $request->spiritual,
            'scorenyeri' => $request->skornyeri ?? 0,
            'keteranganscorenyeri' => $request->keteranganskorenyeri ?? '',
            'kesadaran' => $request->kesadaran ?? '',
            'kesadarane' => $request->kesadarane ?? 0,
            'kesadaranm' => $request->kesadaranm ?? 0,
            'kesadaranv' => $request->kesadaranv ?? 0,
            // baru
            'statusneurologis' => $request->statusneurologis,
            'muakuloskeletal' => $request->muakuloskeletal,
            'tinggibadan' => $request->tinggibadan,
            'beratbadan' => $request->beratbadan,
            'vas' => $request->vas,
            // Khusus Paru
            'inspeksi' => $request->inspeksi,
            'palpasi' => $request->palpasi,
            'perkusidadakanan' => $request->perkusidadakanan,
            'perkusidadakiri' => $request->perkusidadakiri,
            'auskultasisuaradasar' => $request->auskultasisuaradasar,
            'auskultasisuaratambahankanan' => $request->auskultasisuaratambahankanan,
            'auskultasisuaratambahankiri' => $request->auskultasisuaratambahankiri,
            'kddokter' => $request->kddokter ?? ''
        ];

        $simpanperiksaan = null;
        if ($request->has('id')) {
            $simpanperiksaan = Pemeriksaanfisik::find($request->id);
            $simpanperiksaan->update($form);
        } else {
            $simpanperiksaan = Pemeriksaanfisik::create($form);
        }




        if (!$simpanperiksaan) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }



        $data = $request->details;
        $params = [];
        $idDet = [];
        foreach ($data as $key => $value) {
            $simpanpemeriksaandetail = [
                'rs236_id' => $simpanperiksaan->id,
                'noreg' => $noreg,
                'norm' => $norm,
                'tgl' => date('Y-m-d H:i:s'),
                'anatomy' => $value['anatomy'],
                'ket' => $value['ket'],
                'ketebalan' => $value['ketebalan'],
                'panjang' => $value['panjang'],
                'width' => $value['width'],
                'height' => $value['height'],
                'penanda' => $value['penanda'],
                'tinggi' => $value['tinggi'],
                'fill' => $value['fill'],
                'angle' => $value['angle'],
                'templategambar' => $value['templategambar'],
                'templateindex' => $value['templateindex'],
                'templatemenu' => $value['templatemenu'],
                'warna' => $value['warna'],
                'x' => $value['x'],
                'y' => $value['y'],
                'user'  => $kdpegsimrs,
            ];
            if (!empty($value['id'])) {
                $idDet[] = $value['id'];
            }

            $params[] = $simpanpemeriksaandetail;
        };

        // update
        // if (!empty($params)) {
        //     $index = 'id';
        //     Batch::update(new Pemeriksaanfisiksubdetail, $params, $index);
        // }
        $deletes = $request->deleteDetails;
        DB::transaction(function () use ($idDet, $params, $deletes) {
            Pemeriksaanfisiksubdetail::whereIn('id', $idDet)->delete();
            Pemeriksaanfisiksubdetail::insert($params);
            Pemeriksaanfisiksubdetail::whereIn('id', $deletes)->delete();
        });


        $matas = [];

        if ($request->has('mata')) {
            foreach ($request->mata as $key => $value) {
                $mata = [
                    'rs236_id' => $simpanperiksaan->id,
                    'rs1' => $noreg,
                    'rs2' => $norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $value['vodawal'] ?? '',
                    'rs5' => $value['vodrefraksi'] ?? '',
                    'rs6' => $value['vodakhir'] ?? '',
                    'rs7' => $value['vosawal'] ?? '',
                    'rs8' => $value['vosrefraksi'] ?? '',
                    'rs9' => $value['vosakhir'] ?? '',
                    'rs10' => $value['tod'] ?? '',
                    'rs11' => $value['tos'] ?? '',
                    'rs12' => $value['fondosod'] ?? '',
                    'rs13' => $value['fondosos'] ?? '',
                    'user' => $kdpegsimrs
                ];
                // if (!empty($value['id'])) {
                //     $mata['id'] = $value['id'];
                //     $matas[] = $mata;
                //     // Polimata::where('id', $value['id'])->update($mata);
                // } else {
                //     Polimata::create($mata);
                // }
                $matas[] = $mata;
            }


            $idpemeriksaan = $simpanperiksaan->id;
            DB::transaction(function () use ($idpemeriksaan, $matas) {
                Polimata::where('rs236_id', $idpemeriksaan)->delete();
                Polimata::insert($matas);
                // Polimata::whereIn('id', $deletes)->delete();
            });
        } else {
            $idpemeriksaan = $simpanperiksaan->id;
            DB::transaction(function () use ($idpemeriksaan) {
                Polimata::where('rs236_id', $idpemeriksaan)->delete();
            });
        }



        $pemeriksaan = $simpanperiksaan->load(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu']);
        return new JsonResponse(
            [
                'message' => 'BERHASIL DISIMPAN',
                'result' => $pemeriksaan
            ],
            200
        );
    }

    public function hapuspemeriksaanfisik(Request $request)
    {
        $cari = Pemeriksaanfisik::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }

        Pemeriksaanfisikdetail::where('rs236_id', $request->id)->delete();
        Pemeriksaanfisiksubdetail::where('rs236_id', $request->id)->delete();
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function simpangambar(Request $request)
    {
        $image = $request->image;

        $name = date('YmdHis');
        $noreg = str_replace('/', '-', $request->noreg);
        $folderPath = "pemeriksaan_fisik/" . $noreg . '/';

        $image_parts = explode(";base64,", $image);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file = $folderPath . $name . '.' . $image_type;

        $imageName = $name . '.' . $image_type;
        // Storage::delete('public/pemeriksaan_fisik/' . $noreg . '/' . $imageName);
        $wew = Storage::disk('remote')->put('public/pemeriksaan_fisik/' . $noreg . '/' . $imageName, $image_base64);

        $simpangambar = Simpangambarpemeriksaanfisik::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'keterangan' => $request->keterangan ?? '',
                'gambar' => $file ?? '',
            ]
        );

        return new JsonResponse(
            [
                'message' => 'BERHASIL DISIMPAN',
                'result' => $simpangambar
            ],
            200
        );
    }

    public function hapusgambar(Request $request)
    {
        $filename = $request->nama;
        $cari = Simpangambarpemeriksaanfisik::where('gambar', $filename)->first();
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        // Storage::delete('public/' . $filename);
        // Storage::disk('remote')->delete('public/' . $filename);
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function historypemeriksaanfisik()
    {

        $history = Pemeriksaanfisik::select(
            "id",
            "rs1",
            "rs2 as norm",
            "rs3 as tgl",
            "rs4",
            "rs5",
            "tingkatkesadaran",
            "pernapasan",
            "sistole",
            "diastole",
            "suhutubuh",
            "tinggibadan",
            "beratbadan",
            "vas",
            "statuspsikologis",
            "sosialekonomi",
            "spiritual",
            "user",
            "ruangan",
            "scorenyeri",
            "keteranganscorenyeri",
            "kesadaran",
            "kesadarane",
            "kesadaranm",
            "kesadaranv",
            "statusneurologis",
            "muakuloskeletal",
            "inspeksi",
            "palpasi",
            "perkusidadakanan",
            "perkusidadakiri",
            "auskultasisuaradasar",
            "auskultasisuaratambahankanan",
            "auskultasisuaratambahankiri",
            "kddokter",
            "created_at",
            "updated_at"
        )
            ->where('rs2', request('norm'))
            ->where('rs3', '<', Carbon::now()->toDateString())
            ->with('gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu', 'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp')
            ->orderby('id', 'DESC')
            ->get()
            ->chunk(10);

        $collapsed = $history->collapse();


        return new JsonResponse($collapsed->all());
    }
}
