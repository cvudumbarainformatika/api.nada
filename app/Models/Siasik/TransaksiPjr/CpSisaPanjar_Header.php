<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpSisaPanjar_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'pengembaliansisapanjar_heder';
    public $timestamps = false;
    public function sisarinci()
    {
        return $this->hasMany(CpSisaPanjar_Rinci::class, 'nopengembaliansisapanjar', 'nopengembaliansisapanjar');
    }
    // public function npdpjr_head()
    // {
    //     return $this->belongsTo(NpdPanjar_Header::class, 'nonpdpanjar', 'nonpdpanjar');
    // }
}
