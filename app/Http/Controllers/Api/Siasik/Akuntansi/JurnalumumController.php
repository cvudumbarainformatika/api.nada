<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Master\Akun_psap13;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalumumController extends Controller
{
    public function akunpsap()
    {
        // $thn= date('Y');
        $akun = Akun_psap13::where('kode4', '!=', '')->get();
        return new JsonResponse($akun);
    }
    public function save_ju(Request $request)
    {
        // $thn= date('Y');
        // $user = auth()->user()->pegawai_id;
        // $pg= Pegawai::find($user);
        // $pegawai= $pg->kdpegsimrs;
        $number = $request->nobukti ?? self::getnumbering();
        $time = date('Y-m-d H:i:s');
        // return $number;
        try{
            DB::beginTransaction();
            $save=JurnalUmum_Header::updateOrCreate(
                [
                    'nobukti'=>$number,
                ],
                [
                    'tanggal'=>$request->tanggal ?? '',
                    'keterangan'=>$request->keterangan ?? '',
                    'tgl_entry'=>$time ?? '',
                    'user_entry'=>$pegawai ?? ''
                ]);
            foreach($request->rincians as $rinci){
                $save->jurnalumum_rinci()->create(
                    [
                        'nobukti' => $save->nonpdls,
                        'kodepasap13' => $rinci['kodepsap13'] ?? '',
                        'uraianpsap13' => $rinci['uraianpsap13'] ?? '',
                        'debet' => $rinci['debet'] ?? '',
                        'kredit' => $rinci['kredit'] ?? '',
                        'jumlah' => $rinci['jumlah'] ?? '',
                        'tgl_entry'=>$time ?? '',
                        'user_entry'=>$pegawai ?? ''
                    ]);
            }
            return new JsonResponse(
                    [
                        'message' => 'Data Berhasil disimpan...!!!',
                        'result' => $save
                    ], 200);
            } catch (\Exception $er) {
                DB::rollBack();
                return new JsonResponse([
                    'message' => 'Ada Kesalahan',
                    'error' => $er
                ], 500);

        }

    }
    public static function getnumbering() {

        date_default_timezone_set('Asia/Jakarta');
        $text = 'JU';
        $year = date('Y');
        $cek = JurnalUmum_Header::count();
        if($cek == null){
            $format = "000001";
            $number = $format.'/'.strtoupper($text).'/'.$year;
        }
        else {
            $get=JurnalUmum_Header::all()->last();
            $format=(int)substr($get->nobukti, 0, 6) + 1;
            if(strlen($format) == 1){
                $format = "00000".$format;
            }else if(strlen($format) == 2){
                $format = "0000".$format;
            }else if(strlen($format) == 3){
                $format = "000".$format;
            }else if(strlen($format) == 4){
                $format = "00".$format;
            }else if(strlen($format) == 5){
                $format = "0".$format;
            }else{
                $format = (int)$format;
            }
            $number = $format.'/'.strtoupper($text).'/'.$year;
        }
        return $number;
    }
}
