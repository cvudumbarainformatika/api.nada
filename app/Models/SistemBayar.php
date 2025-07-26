<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SistemBayar extends Model
{
    use HasFactory;
    protected $table = 'rs9';
    protected $connection = 'mysql';

    public function kunjungan_rawat_inap()
    {
        return $this->hasMany(KunjunganRawatInap::class, 'rs14');
    }
    public function kunjungan_poli()
    {
        return $this->hasMany(KunjunganPoli::class, 'rs14');
    }
}
