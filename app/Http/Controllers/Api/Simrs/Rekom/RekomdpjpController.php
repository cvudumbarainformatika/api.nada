<?php

namespace App\Http\Controllers\Api\Simrs\Rekom;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rekom\Rekomdpjp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekomdpjpController extends Controller
{
    public function rekomdpjpcon()
    {
        $query = Rekomdpjp::with([
            'relkunjunganpoli.relmpoli',
            'relkunjunganranap.ruangan'
        ])
            ->where('norm', '=', request(['norm']))->whereIn('kdSaran', ['6', '9'])->whereNull('tglBatal')
            ->limit(50)
            ->orderBy('id', 'DESC')
            ->get();
        return new JsonResponse($query);
    }
}
