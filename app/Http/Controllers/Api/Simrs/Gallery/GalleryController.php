<?php

namespace App\Http\Controllers\Api\Simrs\Gallery;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\GalleryResource;
use App\Models\Simrs\Gallery\Gallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index()
    {
        $data = Gallery::latest()->paginate(request('per_page'));
        return response()->json($data);
    }
    public function upload(Request $request)
    {

        if ($request->hasFile('images')) {
            // return response()->json($request->images);
            $files = $request->file('images');

            if (!empty($files)) {

                for ($i = 0; $i < count($files); $i++) {
                    $file = $files[$i];
                    $originalname = $file->getClientOriginalName();
                    $data = Gallery::where('original', $originalname)->first();
                    // Storage::delete('public/gallery/' . $originalname);
                    // Storage::disk('remote')->delete('public/gallery/' . $originalname);
                    $gallery = null;
                    if ($data) {
                        $gallery = $data;
                    } else {
                        $gallery = new Gallery();
                    }
                    $path = $file->storeAs('public/gallery/', $originalname, 'remote');
                    // $path = Storage::disk('remote')->putFileAs('/', $file, $originalname);
                    $gallery->nama = $path;
                    $gallery->original = $originalname;
                    $gallery->save();
                    // Gallery::updateOrCreate(['original'=>$originalname],
                    // ['nama'=> $path]);
                }
                return new JsonResponse(['message' => 'success'], 201);
            }
        }
    }
}
