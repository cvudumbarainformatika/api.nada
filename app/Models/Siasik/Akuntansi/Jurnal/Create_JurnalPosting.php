<?php

namespace App\Models\Siasik\Akuntansi\Jurnal;

use App\Models\Siasik\Master\Akun50_2024;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Create_JurnalPosting extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'jurnal_postingotom';

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
    public function penyesuaian()
    {
        return $this->hasMany(JurnalUmum_Rinci::class, 'kodepsap13', 'kode6');
    }


}
