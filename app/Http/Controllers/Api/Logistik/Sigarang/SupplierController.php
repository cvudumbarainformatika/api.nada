<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\SupplierResource;
use App\Models\Sigarang\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $data = Supplier::latest('id')->filter(request(['q']))->paginate(request('per_page'));

        return SupplierResource::collection($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }
}
