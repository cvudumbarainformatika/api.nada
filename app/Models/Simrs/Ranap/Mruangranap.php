<?php

namespace App\Models\Simrs\Ranap;

use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Penunjang\Keperawatan\Keperawatan;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mruangranap extends Model
{
    use HasFactory;
    protected $table = 'rs24';
    protected $gurded = ['id'];
    public $timestamps = false;
    protected $keyType = 'string';
    protected $connection = 'mysql';

    public function rstigalimax()
    {
        return $this->hasMany(Rstigalimax::class, 'rs16', 'rs4');
    }

    public function akomodasikamar()
    {
        return $this->hasMany(Rstigalimax::class, 'rs18', 'rs1');
    }

    public function tindakandokter()
    {
        return $this->hasMany(Tindakan::class, 'rs25', 'rs1');
    }

    public function tindakanperawat()
    {
        return $this->hasMany(Tindakan::class, 'rs25', 'rs1');
    }

    public function keperawatan()
    {
        return $this->hasMany(Keperawatan::class, 'rs8', 'rs4');
    }

    public function kunjunganranap()
    {
        return $this->hasMany(Kunjunganranap::class, 'rs5', 'rs1');
    }

    public function visiteumum()
    {
        return $this->hasMany(Visite::class, 'rs8', 'rs4');
    }

    public function ruang()
    {
        return $this->setConnection('kepex')->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }

}
