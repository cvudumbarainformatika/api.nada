<?php

namespace App\Models\Siasik\Master;

use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapping_Bidang_Ptk_Kegiatan extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'mappingpptkkegiatan';
    public $timestamps = false;
    public function anggaran()
    {
        return $this->hasMany(PergeseranPaguRinci::class, 'kodekegiatanblud', 'kodekegiatan');
    }
}
