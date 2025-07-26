<?php

namespace App\Http\Controllers\Api\settings;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pegawai\Akses\Aplikasi;
use App\Models\Pegawai\Akses\Menu as AksesMenu;
use App\Models\Pegawai\Akses\Submenu as AksesSubmenu;
use App\Models\Sigarang\Pegawai;
use App\Models\Submenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{

    public function index()
    {
        $data = Menu::with('submenu')->get();

        return new JsonResponse($data);
    }

    public function aplikasi()
    {
        $data = Aplikasi::with(['menus.submenus'])->orderBy('id', 'DESC')->get();
        return new JsonResponse($data);
    }
    public function aplikasi_store(Request $request)
    {
        // return new JsonResponse($request->all());
        $id = $request->id;

        $data = Aplikasi::updateOrCreate(['id' => $id], [
            'nama' => $request->julukan,
            'julukan' => $request->julukan,
            'icon' => $request->icon,
            'aplikasi' => $request->aplikasi,
            'color' => $request->color,
            'singkatan' => $request->singkatan,
            'url' => $request->url,
        ]);
        return new JsonResponse($data);
    }

    public function menuStore(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'aplikasi_id' => 'required'
        ]);
        if ($valid->fails()) {
            return new JsonResponse($valid->errors(), 422);
        }

        // return new JsonResponse($request->all());

        $id = $request->id;

        $data = AksesMenu::updateOrCreate(['id' => $id], [
            'aplikasi_id' => $request->aplikasi_id,
            'link' => $request->link,
            'icon' => $request->icon,
            'nama' => $request->nama,
            'name' => $request->name,
        ]);

        return new JsonResponse(($data));
    }
    public function submenuStore(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'menu_id' => 'required'
        ]);
        if ($valid->fails()) {
            return new JsonResponse($valid->errors(), 422);
        }

        // return new JsonResponse($request->all());

        $id = $request->id;

        $data = AksesSubmenu::updateOrCreate(['id' => $id], [
            'menu_id' => $request->menu_id,
            'link' => $request->link,
            'icon' => $request->icon,
            'nama' => $request->nama,
            'name' => $request->name,
        ]);

        return new JsonResponse(($data));
    }

    public function cariPegawai()
    {
        $data = Pegawai::with('ruangan', 'ruang', 'role', 'user.akses', 'poli', 'depoSim', 'depo','unitpengelolaharsip')
            ->where('aktif', '=', 'AKTIF')
            ->OrWhere('aktif', '=', 'PROGRAMER')
            ->filter(request(['q']))
            ->orderBy('nama', 'ASC')->limit(20)->get();
        return new JsonResponse($data);
    }
    public function cari_dokter()
    {
        $data = Pegawai::select('id', 'nip', 'nik', 'nama', 'kelamin', 'foto', 'kdpegsimrs', 'kddpjp')

            // ->OrWhere('aktif', '=', 'PROGRAMER')
            ->filter(request(['q']))
            ->where('aktif', '=', 'AKTIF')
            ->where('kdgroupnakes', '=', '1')
            ->orderBy('nama', 'ASC')->limit(20)->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $saved = null;
        if ($request->has('id')) {
            $saved = Menu::find($request->id)->update(['nama' => $request->nama]);
        } else {
            $saved = Menu::create(['nama' => $request->nama]);
        }

        if (!$saved) {
            return new JsonResponse(['message' => 'Maaf data tidak tersimpan, Error'], 500);
        }
        return new JsonResponse(['message' => 'Success!! data sudah tersimpan'], 201);
    }

    public function store_submenu(Request $request)
    {
        // $saved = null;
        // if ($request->has('id')) {
        //     $saved = Menu::find($request->id)->update(['nama' => $request->nama]);
        // } else {
        //     $saved = Menu::create(['nama' => $request->nama]);
        // }

        $saved = Submenu::updateOrCreate(
            ['id' => $request->id, 'menu_id' => $request->menu_id],
            ['nama' => $request->nama]
        );

        if (!$saved) {
            return new JsonResponse(['message' => 'Maaf data tidak tersimpan, Error'], 500);
        }
        return new JsonResponse(['message' => 'Success!! data sudah tersimpan'], 201);
    }

    public function delete(Request $request)
    {
        $data = Menu::find($request->id);
        $data->submenu()->delete();
        $del = $data->delete();
        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }
        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
    public function delete_submenu(Request $request)
    {
        $data = Submenu::find($request->id);
        $del = $data->delete();
        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }
        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
