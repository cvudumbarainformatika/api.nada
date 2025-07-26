<?php

namespace App\Models\Siasik\Akuntansi\Jurnal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurnal_Posting extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'jurnal_posting';
    // public $timestamps = false;



    // UNTUK DATA ARRAY dalam 1 KOLOM
    // protected $casts = [
    //     // 'notrans' => 'array',
    //     // 'kegiatanblud' => 'array',
    //     // 'keterangan' => 'array',
    //     // 'koderek' => 'array',
    //     // 'uraianrek' => 'array',
    //     'debit' => 'array',
    //     'kredit' => 'array',
    //     'd_pjk' => 'array',
    //     'k_pjk' => 'array',
    //     'd_pjk1' => 'array',
    //     'k_pjk1' => 'array',
    // ];
}
