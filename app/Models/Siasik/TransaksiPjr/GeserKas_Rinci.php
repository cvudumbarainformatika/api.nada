<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeserKas_Rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'pergeseranTrinci';
    public $timestamps = false;
    // public function npkhead()
    // {
    //     return $this->hasMany(NpkPanjar_Header::class, 'nonpk', 'nonpk');
    // }
    public function sisapjr_head()
    {
        return $this->belongsTo(CpSisaPanjar_Header::class, 'nonpk', 'nopengembaliansisapanjar');
    }
    public function cppjr_head()
    {
        return $this->belongsTo(CpPanjar_Header::class, 'nonpk', 'nopengembalianpanjar');
    }
    public function npkrinci()
    {
        return $this->hasMany(NpkPanjar_Rinci::class, 'nonpk', 'nonpk');
    }
}
