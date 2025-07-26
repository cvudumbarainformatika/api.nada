<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RuanganController extends Controller
{
    public function listruanganranap()
    {
        // $list = Mruangranap::select('groups', 'groups_nama')
        //     ->groupby('groups')
        //     ->where('hiddens', '')
        //     ->get();
        $list = Cache::remember('ruanganranap', now()->addDays(7), function () {
            return Mruangranap::select('groups', 'groups_nama')
            ->groupby('groups')
            ->where('hiddens', '')
            ->get();
        });
        return new JsonResponse($list);
    }
}
