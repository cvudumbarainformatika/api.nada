<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Pembayarannontunai;
use App\Models\Simrs\Kasir\Tagihannontunai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlagingManualVaController extends Controller
{
    public function listva()
    {
        $status = request('status') ?? 'x';
        $list = Tagihannontunai::select('rs297.id as id','rs297.rs1 as nota','rs297.rs2 as nama','rs297.rs4 as nova','rs297.rs6 as tgled','rs297.rs9 as nominal',
        'rs297.rs10 as kasir','rs298.rs2 as tglbyr')
        ->leftjoin('rs298','rs297.rs4','rs298.rs1')
        ->where('rs297.rs12','!=','1')
        ->where(function ($query) {
            $query->where ('rs297.rs4','LIKE','%'. request('q').'%')
                ->orWhere('rs297.rs2', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs297.rs1', 'LIKE', '%' . request('q') . '%');
        })
        ->where(function ($sts) use ($status) {
            if ($status !== 'all') {
                if ($status === 'x') {
                    $sts->whereNull('rs298.rs1');
                } else {
                    $sts->WhereNotNull('rs298.rs1');
                }
            }
        })
        ->orderby('rs297.id', 'Desc')
        ->paginate(request('per_page'));

        return new JsonResponse( $list);
    }

    public function flagingmanual(Request $request)
    {
        try{
            DB::beginTransaction();
                $wew = FormatingHelper::session_user();
                $kdpegsimrs = $wew['kodesimrs'];
                $simpan = Pembayarannontunai::create(
                    [
                        'rs1' => $request->nova,
                        'rs2' => date('Y-m-d H:i:s'),
                        'rs3' => $request->total,
                        'rs5' => $kdpegsimrs,
                        'rs6' => "RSUD"
                    ]
                );

                $update = Tagihannontunai::where('rs4', $request->nova)->first();
                $update->rs12 = 2;
                $update->save();
           DB::commit();
                $hasil = self::getbynova($request->nova);
                return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $hasil],200);
        } catch (\Exception $e) {
           DB::rollBack();
            return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }

    public static function getbynova($nova)
    {
        $list = Tagihannontunai::select('rs297.id as id','rs297.rs1 as nota','rs297.rs2 as nama','rs297.rs4 as nova','rs297.rs6 as tgled','rs297.rs9 as nominal',
        'rs297.rs10 as kasir','rs298.rs2 as tglbyr')
        ->leftjoin('rs298','rs297.rs4','rs298.rs1')
        ->where('rs297.rs4', $nova)
        ->get();

        return $list;
    }
}
