<?php

namespace App\Models\Siasik\TransaksiLS;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpdLS_heder extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'npdls_heder';
    public $timestamps = false;
    // protected $appends = ['nip'];

    // public function getNipAttribute()
    // {
    //     $string = $this->kodepptk;
    //     $newstring = str_replace(' ', '', $string);
    //     return $newstring;
    // }
    public function npdlsrinci()
    {
        return $this->hasMany(NpdLS_rinci::class, 'nonpdls', 'nonpdls');
    }

    public function npkrinci()
    {
        return $this->belongsTo(NpkLS_rinci::class, 'nonpdls', 'nonpdls');
    }
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'nip', 'nip');
    }
    public function pajak()
    {
        return $this->belongsTo(TransPajak::class, 'nonpdls', 'nonpdls');
    }
    public function newpajak()
    {
        return $this->hasMany(NewpajakNpdls::class, 'nonpdls', 'nonpdls');
    }


}
