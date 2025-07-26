<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpSisaPanjar_Rinci extends Model
{
    use HasFactory;
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'pengembaliansisapanjar_rinci';
    public $timestamps = false;
    // public function kasrinci()
    // {
    //     return $this->hasMany(GeserKas_Rinci::class, 'notrans', 'notrans');
    // }
}
