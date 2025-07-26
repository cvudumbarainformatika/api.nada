<?php

namespace App\Http\Controllers\Api\Simrs\Bpjs;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CekkingBpjsController extends Controller
{
    public function ceksep()
    {
        return BridgingbpjsHelper::get_url('vclaim', '/SEP/' . request('nosep'));
    }
}
