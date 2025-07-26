<?php

namespace App\Models\Simrs\Penunjang\Cathlab;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mruangan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReqCathlab extends Model
{
    use HasFactory;
    protected $table = 'cathlab_req';
    protected $guarded = ['id'];

    public function pasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'norm');
    }

    public function ruangan()
    {
        return $this->hasOne(Mruangan::class, 'rs1', 'kd_ruangkelas');
    }

    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'dokterpengirim');
    }

    public function cathlab()
    {
        return $this->hasMany(TransCathlab::class, 'nota', 'nota');
    }



}
