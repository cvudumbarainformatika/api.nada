<?php

namespace App\Http\Controllers\Api\Simrs\Master\Keuangan;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Msistembayar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SistemBayarController extends Controller
{
    public static function listsistembayar()
    {
        // return 'OK';
        $data = Msistembayar::where('hidden', '1')->where('groups', '!=', '')->where('rs5','1')
            ->orderBy('groups', 'asc') // tambahkan ini
            ->orderBy('rs2', 'asc')
            ->where(function ($query) {
                $query->where('rs1', 'Like', '%' . request('q') . '%')
                      ->orWhere('rs2','Like', '%' . request('q') . '%');
            })
            ->get();
        return new JsonResponse($data);
    }

    public function simpan(Request $request)
    {
        if($request->group === '1'){
            $groups = '1';
            $groupLabel = 'BPJS';
        }else if($request->group === '2'){
            $groups = '2';
            $groupLabel = 'Umum';
        }else{
            $groups = '3';
            $groupLabel = 'TAGIHAN';
        }
        if($request->kode === '' || $request->kode === null) {
            DB::select('call msistembayar(@nomor)');
            $hcounter = DB::table('rs1')->select('sistemBayar')->get();
            $wew = $hcounter[0]->sistemBayar;
            $kode = $wew.'-KDS';

            $data = Msistembayar::create(
                [
                    'rs1' => $kode,
                    'rs2' => $request->nama,
                    'rs5' => '1',
                    'groups' => $groups,
                    'hidden' => '1',
                    'rs9' =>$groupLabel
                ]
            );
            $hasil = self::listsistembayar();
            return new JsonResponse($hasil);
        } else {
            $kode = $request->kode;

            $data = Msistembayar::where('rs1', $kode)->update(
                [
                    'rs2' => $request->nama,
                    'rs5' => '1',
                    'groups' => $groups,
                    'hidden' => '1',
                    'rs9' =>$groupLabel
                ]
            );
            $hasil = self::listsistembayar();
            return new JsonResponse($hasil);
        }

        // if($request->group === '1') {
        //    $grousp = 'BPJS';
        // }else if($request->group === '2') {
        //    $grousp = 'Umum';
        // }else{
        //       $grousp = 'TAGIHAN';
        // }
    }

    public function delete(Request $request)
    {
        $data = Msistembayar::where('rs1', $request->id)->first();
        $data->hidden = '';
         $data->rs5 = '';
        $data->save();
        $hasil = self::listsistembayar();
        return new JsonResponse($hasil);
    }
}
