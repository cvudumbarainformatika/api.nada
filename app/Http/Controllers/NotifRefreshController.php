<?php

namespace App\Http\Controllers;

use App\Events\RefreshEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class NotifRefreshController extends Controller
{

    public function index()
    {
      
      $message = [
        'data' => 'Ada Update Aplikasi , Silahkan Reload Halaman Anda'
      ];
      event(new RefreshEvent($message));
      echo 'ok';

    }
}
