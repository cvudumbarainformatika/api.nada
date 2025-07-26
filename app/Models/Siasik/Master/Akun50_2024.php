<?php

namespace App\Models\Siasik\Master;

use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Rinci;
use App\Models\Siasik\Akuntansi\SaldoAwal;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Rinci;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akun50_2024 extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'akun50_2024';
    public $timestamps = false;
    protected $appends = ['kodeall'];
    public function getKodeallAttribute(){
        return "{$this->akun}.{$this->kelompok}.{$this->jenis}.{$this->objek}.{$this->rincian_objek}.{$this->subrincian_objek}";
    }
    public function npdls_rinci(){
        return $this->hasMany(NpdLS_rinci::class,'koderek50', 'kodeall2');
    }
    public function spjpanjar(){
        return $this->hasMany(SpjPanjar_Rinci::class,'koderek50', 'kodeall2');
    }
    public function cp(){
        return $this->hasMany(Contrapost::class,'koderek50', 'kodeall2');
    }
    public function anggaran(){
        return $this->hasMany(PergeseranPaguRinci::class,'koderek50', 'kodeall2');
    }
    public function silpaanggaran(){
        return $this->hasMany(SisaAnggaran::class,'koderek50', 'kodeall3');
    }

    public function saldoawal(){
        return $this->hasMany(SaldoAwal::class,'kodepsap13', 'kodeall3');
    }
    public function jurnalotom(){
        return $this->hasMany(Create_JurnalPosting::class,'kode', 'kodeall3');
    }
    public function penyesuaianx(){
        return $this->hasMany(JurnalUmum_Rinci::class,'kodepsap13', 'kodeall3');
    }
    // public function penyesuaian()
    // {
    //     return $this->belongsTo(JurnalUmum_Rinci::class, 'kodepsap13', 'kodeall3');
    // }


    // untuk anggaran dan belanja
    public function kode1(){
        return $this->belongsTo(Akun50_2024::class,'kode1', 'kodeall2');
    }
    public function kode2(){
        return $this->belongsTo(Akun50_2024::class,'kode2', 'kodeall2');
    }
    public function kode3(){
        return $this->belongsTo(Akun50_2024::class,'kode3', 'kodeall2');
    }
    public function kode4(){
        return $this->belongsTo(Akun50_2024::class,'kode4', 'kodeall2');
    }
    public function kode5(){
        return $this->belongsTo(Akun50_2024::class,'kode5', 'kodeall2');
    }

    // untuk pembiayaan
    // public function kodebiaya1(){
    //     return $this->belongsTo(Akun50_2024::class,'kode1', 'kodeall3');
    // }
    // public function kodebiaya2(){
    //     return $this->belongsTo(Akun50_2024::class,'kode2', 'kodeall3');
    // }
    // public function kodebiaya3(){
    //     return $this->belongsTo(Akun50_2024::class,'kode3', 'kodeall3');
    // }
    // public function kodebiaya4(){
    //     return $this->belongsTo(Akun50_2024::class,'kode4', 'kodeall3');
    // }
    // public function kodebiaya5(){
    //     return $this->belongsTo(Akun50_2024::class,'kode5', 'kodeall3');
    // }
}
