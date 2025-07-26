<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Msapaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SapaanController extends Controller
{
    public function index()
    {
    //    $query = Msapaan::query()
    //    ->selectRaw('id1 as kode,rs2 as sapaan,rs1 as kodex')
    //    ->get();

        $query = Cache::remember('sapaan', now()->addDays(7), function () {
            return Msapaan::query()
            ->selectRaw('id1 as kode,rs2 as sapaan,rs1 as kodex')
            ->get();
        });

        return new JsonResponse($query);
    }
}
