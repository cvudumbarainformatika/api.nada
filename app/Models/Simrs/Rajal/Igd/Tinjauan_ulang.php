<?php

namespace App\Models\Simrs\Rajal\Igd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tinjauan_ulang extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'peninjauan_ulang_igd';
    protected $guarded = ['id'];


    public function tinjauanulangnips()
    {
        return $this->hasOne(Tinjauan_ulang_nips::class, 'id_heder','id');
    }

    public function tinjauanulangbps()
    {
        return $this->hasOne(Tinjauan_ulang_bps::class, 'id_heder','id');
    }
}
