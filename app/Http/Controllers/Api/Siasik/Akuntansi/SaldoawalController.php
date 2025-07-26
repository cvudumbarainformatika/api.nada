<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\SaldoAwal;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaldoawalController extends Controller
{
    public function akunsaldo(){
        $akun=Akun50_2024::where('subrincian_objek', '!=', '')
        ->select('uraian','kodeall3')
        ->when(request('q'), function($q){
            $q->where('uraian', 'LIKE', '%'.request('q').'%');
        })
        ->get();
        return new JsonResponse($akun);
    }

    public function index(){
        $year=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $saldo=SaldoAwal::where('tahun', $year)
        ->select('kodepsap13',
                        'uraianpsap13',
                        'debetkredit',
                        'debit',
                        'kredit',
                        'tahun',
                        'id',
                )
                ->orderBy('kodepsap13', 'asc')
                ->get();
        return new JsonResponse($saldo);
    }
    public function save(Request $request){
        $user = auth()->user()->pegawai_id;
        $pg= Pegawai::find($user);
        $pegawai= $pg->kdpegsimrs;
        $year=Carbon::createFromFormat('Y', request('tahun'))->format('Y');
        $time = date('Y-m-d H:i:s');
        $date = Carbon::create($year, 1, 1)->format('Y-m-d');

        if (!$request->has('id')){
            $saldo=SaldoAwal::firstOrCreate(
            [
                            'kodepsap13' => $request['kodepsap13'],
                            'uraianpsap13' => $request['uraianpsap13'],
                            'debetkredit' => $request['debetkredit'],
                            'debit' => $request['debit'],
                            'kredit' => $request['kredit'],
                            'tahun' => $year ?? '',
                            'tglentry' => $time ?? '',
                            'tanggal' => $date ?? '',
                            'userentry'=> $pegawai ?? ''
                        ]);
        } else {
            $editsaldo = SaldoAwal::find($request->id);
            $editsaldo->update($request->only([
                'kodepsap13',
                'uraianpsap13',
                'debetkredit',
                'debit',
                'kredit'
            ]));
            return new JsonResponse(['message' => 'Saldo Berhasil diedit', 'result'=> $editsaldo],200);
        }
        return new JsonResponse(['message' => 'Data Berhasil disimpan...!!!', 'result'=> $saldo],200);
    }
    public function destroy(Request $request)
    {
        $id = $request->id;
        $data = SaldoAwal::where('id', $id);
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
