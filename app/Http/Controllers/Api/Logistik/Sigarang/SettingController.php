<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $data = Setting::get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), [
                'nama' => 'required'
            ]);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }
            Setting::updateOrCreate(['nama' => $request->nama], $request->all());

            DB::commit();

            return new JsonResponse(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
}
