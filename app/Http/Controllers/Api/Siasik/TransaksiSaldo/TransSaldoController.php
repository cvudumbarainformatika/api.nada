<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiSaldo;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\RekeningBank;
use App\Models\Siasik\TransaksiSaldo\SaldoAwal_PPK;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
// use App\Models\Sigarang\Rekening50;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransSaldoController extends Controller
{
    public function lihatrekening() {
        $saldo = RekeningBank::where('noRek', '=', '0121161061')->get();
        // ->where('noRek', request('rek'))

        return new JsonResponse( $saldo);

    }
    public function tabelrek() {
        $thn=date('Y');
        // $awal = $thn.'-01-01';
        // $akhir=request('tglx', 'Y-m-d');
        $saldo = SaldoAwal_PPK::where('tahun', $thn)
        ->when(request('q'), function($x){
            $x->where('rekening', 'LIKE', '%' . request('q') . '%')
            ->orWhere('noregister', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namaRek', 'LIKE', '%' . request('q') . '%');
            ;
        })
        // ->whereBetween('tanggal', [$awal, $akhir])
        ->paginate(request('per_page'));
        // ->where('noRek', request('rek'))

        return new JsonResponse( $saldo);

    }
    public function transSaldo(Request $request){
        $nomor = $request->noregister ?? self::buatnomor();
        $simpan = SaldoAwal_PPK::updateOrCreate(
            [
                'noregister'=> $nomor,
            ],
            [
                'tanggal' => $request->tanggal,
                'tahun'=> date('Y'),
                'rekening' => $request->rekening,
                'namaRek' => $request->namaRek,
                'nilaisaldo' => $request->nilaisaldo
            ]
        );
        if (!$simpan){
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }else {
            return new JsonResponse(['message' => 'Berhasil di Simpan'], 200);
        }
    }
    public function hapussaldo(Request $request){
        $id=$request->id;
        $data=SaldoAwal_PPK::find($id);
        $del=$data->delete();
        if(!$del){
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
    public static function buatnomor(){
        $huruf = ('SALDOAWAL-BLUD');
        // $no = ('4.02.0.00.0.00.01.0000');
        date_default_timezone_set('Asia/Jakarta');
        // $tgl = date('Y/m/d');
        $thn = date('Y');
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        // $time = date('mis');
        // $nomer=Transaksi::latest();
        $cek = SaldoAwal_PPK::count();
        if ($cek == null){
            $urut = "0001";
            $sambung = $urut.'/'.strtoupper($huruf).'/'.$rom[date('n')].'/'.$thn;
        }
        else{
            $ambil=SaldoAwal_PPK::all()->last();
            $urut = (int)substr($ambil->noregister, 1, 3) + 1;
            //cara menyambungkan antara tgl dn kata dihubungkan tnda .
            // $urut = "000" . $urut;
            if(strlen($urut) == 1){
                $urut = "000" . $urut;
            }
            else if(strlen($urut) == 2){
                $urut = "00" . $urut;
            }
            else if(strlen($urut) == 3){
                $urut = "0" . $urut;
            }
            else {
                $urut = (int)$urut;
            }
            $sambung = $urut.'/'.strtoupper($huruf).'/'.$rom[date('n')].'/'.$thn;
        }

        return $sambung;
    }
}
