<?php

namespace App\Http\Controllers\Api\Simrs\UnitPelayananArsip;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\MorganisasiAdministrasi;
use App\Models\Simrs\UnitPengelolahArsip\Dataarsip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ListDataArsipController extends Controller
{
    public function listdataarsip()
    {
        if (request('bidangbagian') === '' || request('bidangbagian') === null) {
            $organisasi = MorganisasiAdministrasi::select('kode')->where('hiddenx', '')->get();
            $raw = collect($organisasi);
            $only = $raw->map(function ($y) {
                return $y->kode;
            });
            $bidangbagian = $only;
        } else {
            $bidangbagian = array(request('bidangbagian'));
        }
        $data = Dataarsip::select(
            'data_arsip.*',
            'master_kode.kode as kodeklasifikasi',
            'master_kode.nama as namakelasifikasi',
            'master_lokasi.nama_lokasi',
            'master_media.nama_media'
        )
            ->join('master_kode', 'data_arsip.kode', 'master_kode.kode')
            ->join('master_lokasi', 'data_arsip.lokasi', 'master_lokasi.id')
            ->join('master_media', 'data_arsip.media', 'master_media.id')
            ->with(
                [
                    'unitpengolah'
                ]
            )
            ->where(function ($query) {
                $query->where('data_arsip.noarsip', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('data_arsip.uraian', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('data_arsip.nobox', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('master_kode.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('master_kode.nama', 'LIKE', '%' . request('q') . '%');
            })
            ->whereIn('data_arsip.unit_pengolah', $bidangbagian)
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }

    public static function getlistdataarsipbynoarsip($noarsip)
    {

        $data = Dataarsip::select('data_arsip.*', 'master_kode.kode as kodeklasifikasi', 'master_kode.nama as namakelasifikasi', 'master_lokasi.nama_lokasi', 'master_media.nama_media')
            ->join('master_kode', 'data_arsip.kode', 'master_kode.kode')
            ->join('master_lokasi', 'data_arsip.lokasi', 'master_lokasi.id')
            ->join('master_media', 'data_arsip.media', 'master_media.id')
            ->where('data_arsip.flaging', '1')
            ->where('data_arsip.noarsip', $noarsip)
            ->get();
        return $data;
    }

    public function simpanarsip(Request $request)
    {
        // return $request->noarsip;
        if ($request->filled('noarsip')) {
            try {
                DB::beginTransaction();
                $update = Dataarsip::where('noarsip', $request->noarsip)->first();
                $update->update([
                    'tanggal' => $request->tgl,
                    'uraian' => $request->uraian,
                    'ket' => $request->keaslian,
                    'kode' => $request->kodekelasifikasi,
                    'jumlah' => $request->jumlah,
                    'nobox' => $request->nobox,
                    'lokasi' => $request->lokasi,
                    'media' => $request->media,
                    'keterangan' => $request->keterangan,
                ]);
                $kirim = self::getlistdataarsipbynoarsip($request->noarsip);
                DB::commit();
                return new JsonResponse(['message' => 'success', 'result' => $kirim, 'update' => 'true'], 200);
            }catch(\Exception $th) {
                DB::rollback();
                return new JsonResponse(['message' => 'invalid dokumen', 'error' => $th->getMessage()], 500);
            }
        } else {
           try {
                DB::beginTransaction();
                $user = FormatingHelper::session_user();
                $kdpegsimrs = $user['kodesimrs'];
                $kdruangarsip = $user['kode_ruang_arsip'];
                $nomor = '@nomor';


                DB::connection('siasik')->select('call noarsip(?,?)', array($nomor, $kdruangarsip));
                $x = DB::connection('siasik')->table('organisasi')->select('counter_arsip', 'panggilan', 'nama')->where('kode', $kdruangarsip)->get();
                $wew = $x[0]->counter_arsip;
                $panggilan = $x[0]->panggilan;
                $pencipta = $kdruangarsip;
                $unit_pengolah = $kdruangarsip;
                $tanggal = explode('-', $request->tgl);
                $tahun = $tanggal[0];
                $noarsip = FormatingHelper::noarsip($wew, $panggilan, $tahun);

                $simpan = Dataarsip::create([
                    'noarsip' => $noarsip,
                    'pencipta' => $pencipta,
                    'unit_pengolah' => $unit_pengolah,
                    'tanggal' => $request->tgl,
                    'uraian' => $request->uraian,
                    'ket' => $request->keaslian,
                    'kode' => $request->kodekelasifikasi,
                    'jumlah' => $request->jumlah,
                    'nobox' => $request->nobox,
                    'lokasi' => $request->lokasi,
                    'media' => $request->media,
                    'keterangan' => $request->keterangan,
                    'username' => $kdpegsimrs,
                    'flaging' => '1',
                ]);
                $kirim = self::getlistdataarsipbynoarsip($noarsip);
            DB::commit();
                    return new JsonResponse(['message' => 'success', 'result' => $kirim], 200);
            }catch(\Exception $th) {
                DB::rollback();
                return new JsonResponse(['message' => 'invalid dokumen', 'error' => $th->getMessage()], 500);
            }
        }
    }


    public function simpanarsipdokumen(Request $request)
    {

        try {
            DB::beginTransaction();


            if (!$request->hasFile('dokumen') || !$request->file('dokumen')->isValid()) {
                return response()->json(['message' => 'File tidak valid atau tidak ada'], 422);
            }

            $file = $request->file('dokumen');
            $noarsip = $request->noarsip;
            $originalname = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            $panggilan = explode('-', $noarsip);
            $panggilan = end($panggilan); // Contoh: ambil "IT" dari 0000007-2025-IT
            $folder = 'dokumen_arsip/' . $panggilan;
            $penamaan = time() . '-' . $noarsip . '.' . $extension;

             // Buat folder di disk jika belum ada
            if (!Storage::disk('remote')->exists('public/'.$folder)) {
                Storage::disk('remote')->makeDirectory('public/'.$folder);
            }

            // if (!Storage::exists($folder)) {
            //     Storage::makeDirectory($folder);
            // }

            $storagePath = $file->storeAs('public/' . $folder, $penamaan, 'remote');
            // $storagePath = $file->storeAs('public/' . $folder, $penamaan);

            if (!$storagePath) {
                return response()->json([
                    'message' => 'Gagal menyimpan file ke storage',
                ], 500);
            }

            $fullPath = "$folder/$penamaan";

             $update = Dataarsip::where('noarsip', $noarsip)->first();
                if ($update) {
                    $update->update([
                        'file'  => $originalname,
                        'path' => $storagePath,
                        'url'  => $fullPath
                    ]);
                }

            $kirim = self::getlistdataarsipbynoarsip($noarsip);
        DB::commit();

            return new JsonResponse(['message' => 'success', 'result' => $kirim], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'Upload gagal',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    // public function simpanarsip(Request $request)
    // {
    //     if ($request->hasFile('dokumen')) {
    //         try {
    //             DB::beginTransaction();
    //             $user = FormatingHelper::session_user();
    //             $kdpegsimrs = $user['kodesimrs'];
    //             $kdruangarsip = $user['kode_ruang_arsip'];
    //             $nomor = '@nomor';


    //             DB::connection('siasik')->select('call noarsip(?,?)', array($nomor, $kdruangarsip));
    //             $x = DB::connection('siasik')->table('organisasi')->select('counter_arsip', 'panggilan', 'nama')->where('kode', $kdruangarsip)->get();
    //             $wew = $x[0]->counter_arsip;
    //             $panggilan = $x[0]->panggilan;
    //             $pencipta = $kdruangarsip;
    //             $unit_pengolah = $kdruangarsip;
    //             $tanggal = explode('-', $request->tgl);
    //             $tahun = $tanggal[0];
    //             $noarsip = FormatingHelper::noarsip($wew, $panggilan, $tahun);
    //             $files = $request->file('dokumen');

    //             //   $user = auth()->user()->pegawai_id;

    //             if (!empty($files)) {

    //                 for ($i = 0; $i < count($files); $i++) {
    //                     $file = $files[$i];

    //                     $originalname = $file->getClientOriginalName();
    //                     $penamaan = $i . '-' . $noarsip . '.' . $file->getClientOriginalExtension();

    //                     $extension = $file->getClientOriginalExtension();

    //                     // return new JsonResponse($extension);
    //                     $data = Dataarsip::where([
    //                         ['noarsip', $noarsip],
    //                         ['file', $originalname]
    //                     ])->first();
    //                     if ($data) {
    //                         // Storage::delete($data->path);
    //                         //   Storage::disk('remote')->delete($data->path);

    //                     }

    //                     $gallery = null;
    //                     if ($data) {
    //                         $gallery = $data;
    //                     } else {
    //                         $gallery = new Dataarsip();
    //                     }

    //                     $folder = 'dokumen_arsip/' . $panggilan;

    //                     // if (!is_dir(storage_path("app/public/$folder"))) {
    //                     //   mkdir(storage_path("app/public/$folder"), 0775, true);
    //                     // }
    //                     if (!Storage::disk('remote')->exists("public/$folder")) {
    //                         Storage::disk('remote')->makeDirectory("public/$folder");
    //                     }


    //                     // // Upload Avatar (IMAGE INTERVENTION - LARAVEL)
    //                     // Image::make($request->file("upload_image"))->save(storage_path("app/public/post-images/".$id.".png"));

    //                     if ($extension !== 'pdf') {

    //                         $img = Image::make($file)->resize(600, null, function ($constraint) {
    //                             $constraint->aspectRatio();
    //                         });

    //                         // $img->save(\public_path("storage/$folder/". $penamaan), 60);
    //                         // Buat file temporary dengan nama random (contoh: /tmp/resize_8jf9d2)
    //                         $tempPath = tempnam(sys_get_temp_dir(), 'resize_');
    //                         $img->save($tempPath, 60);

    //                         // $img->save(\public_path("storage/$folder/". $penamaan), 60);
    //                         // Upload ke remote dengan nama file yang kita inginkan ($penamaan)
    //                         // Contoh $penamaan: 20240219123456-1-123456.jpg
    //                         Storage::disk('remote')->put(
    //                             "public/$folder/$penamaan",  // full path dengan nama file
    //                             file_get_contents($tempPath)  // isi file
    //                         );

    //                         // Hapus file temporary
    //                         unlink($tempPath);
    //                     } else {
    //                         // $path = $file->storeAs('public/'.$folder, $penamaan);
    //                         $path = $file->storeAs('public/' . $folder, $penamaan, 'remote');
    //                     }

    //                     $gallery->noarsip = $noarsip;
    //                     $gallery->pencipta = $pencipta;
    //                     $gallery->unit_pengolah = $unit_pengolah;
    //                     $gallery->tanggal = $request->tgl;
    //                     $gallery->uraian = $request->uraian;
    //                     $gallery->ket = $request->keaslian;
    //                     $gallery->kode = $request->kodekelasifikasi;
    //                     $gallery->jumlah = $request->jumlah;
    //                     $gallery->nobox = $request->nobox;
    //                     $gallery->lokasi = $request->lokasi;
    //                     $gallery->media = $request->media;
    //                     $gallery->file = $originalname;
    //                     $gallery->username = $kdpegsimrs;
    //                     $gallery->flaging = '1';

    //                     $gallery->path = "public/$folder/$penamaan";
    //                     $gallery->url = $folder . '/' . $penamaan;
    //                     $gallery->keterangan = $request->keterangan;
    //                     $gallery->save();
    //                 }

    //                 $kirim = self::getlistdataarsipbynoarsip($noarsip);
    //                 DB::commit();
    //                 return new JsonResponse(['message' => 'success', 'result' => $kirim], 200);
    //             }
    //         } catch (\Exception $th) {
    //             DB::rollback();
    //             return new JsonResponse(['message' => 'invalid dokumen', 'error' => $th->getMessage()], 500);
    //         }
    //     } else {
    //         $update = Dataarsip::where('noarsip', $request->noarsip)->first();
    //         $update->update([
    //             'uraian' => $request->uraian,
    //             'ket' => $request->keaslian,
    //             'kode' => $request->kodekelasifikasi,
    //             'jumlah' => $request->jumlah,
    //             'nobox' => $request->nobox,
    //             'lokasi' => $request->lokasi,
    //             'media' => $request->media,
    //             'keterangan' => $request->keterangan,
    //         ]);
    //         $kirim = self::getlistdataarsipbynoarsip($request->noarsip);
    //         return new JsonResponse(['message' => 'success', 'result' => $kirim, 'update' => 'true'], 200);
    //     }
    // }
}
