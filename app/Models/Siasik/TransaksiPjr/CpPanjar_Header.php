<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpPanjar_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'pengembalianpanjar_heder';
    public $timestamps = false;
    public function cppjr_rinci()
    {
        return $this->hasMany(CpPanjar_Rinci::class, 'nopengembalianpanjar', 'nopengembalianpanjar');
    }
}
