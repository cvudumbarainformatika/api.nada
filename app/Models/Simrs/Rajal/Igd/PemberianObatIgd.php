<?php

namespace App\Models\Simrs\Rajal\Igd;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemberianObatIgd extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'pemakaianobat';
    protected $guarded = ['id'];


    public function mobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }

    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'userinput');
    }
}
