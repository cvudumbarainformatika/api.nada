<?php

namespace App\Models\Siasik\Anggaran;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyesuaian_Prioritas_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'penyesesuaianperioritas_heder';

    public function rincian(){
        return $this->hasMany(Penyesuaian_Prioritas_Rinci::class,'notrans', 'notrans');
    }
    public function rincianpergeseran(){
        return $this->hasMany(Perubahan_RincianBelanja::class,'notrans', 'notrans');
    }

}
