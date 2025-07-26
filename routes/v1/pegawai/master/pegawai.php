<?php

use App\Http\Controllers\Api\Pegawai\Master\CutiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/master'
], function () {
    Route::get('/pegawai-by-kdpegsimrs', function () {
      
      $data = DB::connection('kepex')->table('pegawai')
        ->select('pegawai.kdpegsimrs', 'pegawai.nama',
        'pegawai.nip','pegawai.nik','pegawai.jabatan','pegawai.golruang',
        'm_jabatan.jabatan as ket_jabatan','m_golruang.golruang as golongan',
        'm_golruang.keterangan as ket_golongan'
        )
        ->leftJoin('m_jabatan', 'pegawai.jabatan', '=', 'm_jabatan.kode_jabatan')
        ->leftJoin('m_golruang', 'pegawai.golruang', '=', 'm_golruang.kode_gol')
        ->where('pegawai.kdpegsimrs', '=', request('kdpegsimrs'))
        ->where('pegawai.aktif', '=', 'AKTIF')
        ->first();

      return response()->json($data);
    });
});
