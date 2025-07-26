<?php

namespace App\Models\Siasik\Anggaran;

use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_jurnal;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Rinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PergeseranPaguRinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 't_tampung';
    public $timestamps = false;

    public function npdls_rinci(){
        return $this->hasMany(NpdLS_rinci::class,'koderek50', 'koderek50');
    }
    public function spjpanjar(){
        return $this->hasMany(SpjPanjar_Rinci::class,'koderek50', 'koderek50');
    }
    public function cp(){
        return $this->hasMany(Contrapost::class,'koderek50', 'koderek50');
    }
    public function masterobat(){
        return $this->hasMany(Mobatnew::class, 'kode108', 'koderek108');
    }
    public function realisasi(){
        return $this->hasMany(NpdLS_rinci::class, 'idserahterima_rinci', 'idpp');
    }
    public function realisasi_spjpanjar(){
        return $this->hasMany(SpjPanjar_Rinci::class, 'iditembelanjanpd', 'idpp');
    }
    public function contrapost(){
        return $this->hasMany(Contrapost::class,'idpp', 'idpp');
    }
    public function jurnal()
    {
        return $this->hasOne(Akun_jurnal::class, 'kodeall2', 'koderek50');
    }

    public function lvl1(){
        return $this->belongsTo(Akun50_2024::class,'kode1', 'kodeall3');
    }
    public function lvl2(){
        return $this->belongsTo(Akun50_2024::class,'kode2', 'kodeall3');
    }
    public function lvl3(){
        return $this->belongsTo(Akun50_2024::class,'kode3', 'kodeall3');
    }
    public function lvl4(){
        return $this->belongsTo(Akun50_2024::class,'kode4', 'kodeall3');
    }
    public function lvl5(){
        return $this->belongsTo(Akun50_2024::class,'kode5', 'kodeall3');
    }

}
