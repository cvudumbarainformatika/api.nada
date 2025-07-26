<?php

namespace App\Models\Siasik\TransaksiSilpa;

use App\Models\Siasik\Master\Akun50_2024;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SisaAnggaran extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'silpa';
    public $timestamps = false;

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
