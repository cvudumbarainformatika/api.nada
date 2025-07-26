<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwaloperasiController extends Model
{
    use HasFactory;
    protected $table = 'operasi_jadwal';
    protected $guarded = ['id'];
    public $timestamps = false;
}
