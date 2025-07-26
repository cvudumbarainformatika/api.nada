<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Video;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Mockery\Undefined;

class VideoController extends Controller
{
    public function index()
    {
        $data = Video::when(request('q'), function ($search, $q) {
            $search->where('nama', 'LIKE', '%' . $q . '%');
        })
            // ->with(['layanan'])
            ->latest()
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $path = null;

        if (!$request->hasFile('video')) {
            return new JsonResponse(['message' => "File Bukan Video"], 500);
        }

        $validator = Validator::make($request->all(), [
            'video' => 'mimes:mp4,webm|unique:antrean.videos,url, ' . $request->id
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('id')) {
            $data = Video::find($request->id);
            $old_path = $data->url;
            if (!empty($old_path)) {
                // Storage::delete('public/' . $old_path);
                // Storage::disk('remote')->delete('public/' . $old_path);
            }
        }

        $path = $request->file('video')->store('videos', 'public');

        $data = Video::updateOrCreate(
            [
                'id' => $request->id,
            ],
            [
                'url' => $path,
                'nama' => $request->nama,
                'type' => $request->type,
            ]
        );

        if (!$data) {
            return new JsonResponse(['message' => "Gagal Menyimpan"], 500);
        }

        return new JsonResponse(['message' => "success"], 200);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $data = Video::find($id);
        // Storage::delete('public/' . $data->url);
        // Storage::disk('remote')->delete('public/' . $data->url);
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



    public function display()
    {
        $data = Video::all();
        return new JsonResponse($data);
    }
}
