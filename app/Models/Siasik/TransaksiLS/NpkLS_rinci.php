<?php

namespace App\Models\Siasik\TransaksiLS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpkLS_rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'npkls_rinci';
    public $timestamps = false;
    // protected $appends = array('totalcair');
    // public function getTotalcairAttribute()
    // {
    //     return $this->total->sum();
    // }

    public function npdlsrinci()
    {
        return $this->hasMany(NpdLS_rinci::class, 'nonpdls', 'nonpdls');
    }
    public function npdlshead()
    {
        return $this->belongsTo(NpdLS_heder::class, 'nonpdls', 'nonpdls');
    }
    // public function cp(){
    //     return $this->hasMany(Contrapost::class,'nonpd','nonpdls');
    // }
    public function header()
    {
        return $this->belongsTo(NpkLS_heder::class, 'nonpk', 'nonpk');
    }
}
