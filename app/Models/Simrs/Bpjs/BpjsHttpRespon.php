<?php

namespace App\Models\Simrs\Bpjs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsHttpRespon extends Model
{
    use HasFactory;
    protected $table = 'bpjs_http_respon';
    protected $guarded = ['id'];
    public $timestamps = false;

    // public static function scopeGetAll($query)
    // {
    //     $query
    //         ->from('bpjs_pasien_baru as a')
    //         ->select([
    //             'a.nomorkartu',
    //             'a.tanggallahir',
    //             'a.nik'
    //         ]);
    // }

    // public static function scopeGetByNoBpjs($query, $noBpjs)
    // {
    //     $query
    //         ->getAll()
    //         ->where('a.nomorkartu', $noBpjs);
    // }
}
