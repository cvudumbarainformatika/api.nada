<?php

namespace App\Http\Controllers\Api\DataCache;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class DataCacheController extends Controller
{
    public function index()
    {
        $contents = File::get(storage_path('../json/listscache.json'));
        return JsonResponse::fromJsonString($contents);
    }

    public function hapusCache(Request $request)
    {
      $nama = $request->nama;
      $withId = $request->ketId;

      

      $key =null;
      if ($withId === null) {
      $key = $nama;
      } else {
      $key = $nama.$withId;
      }

      // return $key;

      Cache::forget($key);

      return response()->json(['message' => 'success'], 200);

    }
}
