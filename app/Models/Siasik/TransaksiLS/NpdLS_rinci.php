<?php

namespace App\Models\Siasik\TransaksiLS;

use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_Kepmendg50;
use App\Models\Siasik\Master\Akun_mapjurnal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpdLS_rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'npdls_rinci';
    public $timestamps = false;
    // public function kodeall()
    // {
    //     return $this->belongsTo(Akun_Kepmendg50::class, 'kodeall', 'koderek50');
    // }
    // public function akun50(){
    //     return $this->hasOne(Akun50_2024::class,'kodeall2', 'koderek50');
    // }
    public function cp(){
        return $this->hasMany(Contrapost::class,'nonpd','nonpdls');
    }
    public function headerls()
    {
        return $this->belongsTo(NpdLS_heder::class, 'nonpdls', 'nonpdls');
    }
    public function mapjurnal()
    {
        return $this->belongsTo(Akun_mapjurnal::class, 'koderek50', 'kodeall');
    }
}
