<?php

namespace App\Models\Simrs\PemeriksaanRMkhusus;

use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polimata extends Model
{
    use HasFactory;
    protected $table = 'rs223';
    protected $guarded = ['id'];


    public function relasivisus()
    {
        return $this->hasOne(Pemeriksaanfisik::class, 'rs236_id', 'id');
    }
}
