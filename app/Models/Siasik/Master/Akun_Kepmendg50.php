<?php

namespace App\Models\Siasik\Master;

use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Rinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akun_Kepmendg50 extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'akun_permendagri50';
    public $timestamps = false;

    protected $appends = ['kodeall'];
    public function getKodeallAttribute(){
        return "{$this->kode1}.{$this->kode2}.{$this->kode3}.{$this->kode4}.{$this->kode5}.{$this->kode6}";
    }
    // public function kodeall(){
    //     return $kodeall->append('kodeall')->ToArray();
    // }
    public function npdls_rinci(){
        return $this->hasMany(NpdLS_rinci::class,'koderek50', 'kodeall');
    }
    public function spjpanjar(){
        return $this->hasMany(SpjPanjar_Rinci::class,'koderek50', 'kodeall');
    }
    public function cp(){
        return $this->hasMany(Contrapost::class,'koderek50', 'kodeall');
    }
}
