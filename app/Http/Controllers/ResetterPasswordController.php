<?php

namespace App\Http\Controllers;

use App\Models\LaboratLuar;
use App\Models\Simpeg\Petugas;
use App\Models\TransaksiLaborat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ResetterPasswordController extends Controller
{

  public function index()
  {

      $akun = User::where('username', request('nik'))->first();
      $pegawai = Petugas::where('nik', request('nik'))->where('aktif', 'AKTIF')->first();


      if (!$akun || !$pegawai) {
          return new JsonResponse(['message' => 'Akun Tidak ditemukan'], 404);
      }

      $password= '123456789';

    //  if (request('password')) {
      $akun->password = bcrypt($password);
      $akun->save();

      $pegawai->account_pass = $password;
      $pegawai->save();

    //  }

      $data = [
        'akun' => $akun,
        'pegawai' => $pegawai
      ];

      return new JsonResponse($data, 200);

      // dd($akun);
      // echo bcrypt('123456789');
  }
    
}
