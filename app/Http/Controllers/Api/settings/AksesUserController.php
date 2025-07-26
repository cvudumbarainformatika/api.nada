<?php

namespace App\Http\Controllers\Api\settings;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Akses\Access;
use App\Models\Pegawai\Akses\AksesUser;
use App\Models\Pegawai\Akses\Role;
use App\Models\Pegawai\Akses\Submenu;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Master\Mpoli;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AksesUserController extends Controller
{
    //
    public function userAkses()
    {
    }
    public function userRole()
    {
        $data = Role::select('id', 'nama')->get();
        return new JsonResponse($data);
    }
    public function getPoli()
    {
        $data = Mpoli::listpoli()->where('rs4', 'Poliklinik')->where('rs5', '=', '1')->get();
        return new JsonResponse($data);
    }
    public function getPenunjang()
    {
        $data = Mpoli::listpoli()->where('rs4', 'Penunjang')->get();
        return new JsonResponse($data);
    }
    public function storeRole(Request $request)
    {
        $data = Pegawai::find($request->id);
        $role = Role::find($request->role_id);
        $data->update([
            'role_id' => $request->role_id
        ]);
        return new JsonResponse($role);
    }
    public function storeRuang(Request $request)
    {
        $data = Pegawai::find($request->id);
        $ruang = Ruang::where('kode', $request->kode_ruang)->first();
        $data->update([
            'kode_ruang' => $request->kode_ruang
        ]);
        return new JsonResponse($ruang);
    }
    public function storePoli(Request $request)
    {
        $data = Pegawai::find($request->id);
        // $poli = Mpoli::where('rs1', $request->kodepoli)->first();
        $data->update([
            'kdruangansim' => $request->kodepoli ?? ''
        ]);
        return new JsonResponse($data);
    }
    public function storeAkses(Request $request)
    {
        $data = [];
        if ($request->tipe === true) {
            foreach ($request->data as $anu) {
                $wew = $this->createAkses($anu);
                array_push($data, $wew);
            }
        } else {
            foreach ($request->data as $anu) {
                $wew = $this->deleteAkses($anu);
                array_push($data, $wew);
            }
        }
        // $data = $request->all();
        return new JsonResponse($data);
    }
    // public function storeAksesMenuOnly(Request $request)
    // {
    //     return new JsonResponse($request);
    // }
    private function createAkses($uncal)
    {
        // return $uncal;
        $data = AksesUser::firstOrCreate(
            [
                'user_id' => $uncal['user_id'],
                'aplikasi_id' => $uncal['aplikasi_id'],
                'menu_id' => $uncal['menu_id'],
                'submenu_id' => $uncal['submenu_id'],
            ]
        );
        return $data;
    }
    private function deleteAkses($uncal)
    {
        $data = AksesUser::where('user_id', $uncal['user_id'])
            ->where('aplikasi_id', $uncal['aplikasi_id'])
            ->where('menu_id', $uncal['menu_id'])
            ->where('submenu_id', $uncal['submenu_id'])
            ->first();
        if ($data) {
            $data->delete();
            return $data;
        }

        return false;
    }

    public function migrasiAkses()
    {
        $aksesPegawai = Pegawai::select('id', 'role_id')->whereNotNull('role_id')->get();
        $data = [];
        foreach ($aksesPegawai as $key) {
            $user = User::where('pegawai_id', $key['id'])->first();
            if ($user) {
                $akses = Access::where('role_id', $key['role_id'])->get();
                foreach ($akses as $aks) {
                    $sub = Submenu::find($aks['submenu_id']);
                    $insert = AksesUser::firstOrCreate([
                        'user_id' => $user->id,
                        'submenu_id' => $sub->id,
                        'menu_id' => $sub->menu_id,
                        'aplikasi_id' => $aks['aplikasi_id']
                    ]);
                    array_push($data, $insert);
                    // return new JsonResponse([
                    //     'user_id' => $user->id,
                    //     'submenu_id' => $sub->id,
                    //     'menu_id' => $sub->menu_id,
                    //     'aplikasi_id' => $aks['aplikasi_id']
                    // ]);
                }
            }
        }

        // $data['akses pegawai'] = $aksesPegawai;
        return new JsonResponse($data);
    }

    public function storeunitpengolaharsip(Request $request)
    {
        $data = Pegawai::find($request->id);
        $data->kdarteri = $request->kode_ruang;
        $data->save();
        $data->load('unitpengelolaharsip');
        // $data->update([
        //     'kode_ruang' => $request->kode_ruang
        // ]);
        return new JsonResponse($data);
    }
}
