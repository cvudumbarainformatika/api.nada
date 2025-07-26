<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpjPanjar_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'spjpanjar_heder';
    public $timestamps = false;
    public function spj_rinci()
    {
        return $this->hasMany(SpjPanjar_Rinci::class, 'nospjpanjar', 'nospjpanjar');
    }


    public function nota()
    {
        return $this->belongsTo(NotaPanjar_Header::class, 'notapanjar', 'nonotapanjar');
    }
}
