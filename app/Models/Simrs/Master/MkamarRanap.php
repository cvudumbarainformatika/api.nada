<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Views\Kunjunganview;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MkamarRanap extends Model
{
    use HasFactory;
    protected $table      = 'rs25';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function pengunjung()
    {
        return $this->hasMany(Kunjunganranap::class, 'rs5', 'bpjskdruang');
    }
    public function kamar()
    {
        return $this->hasOne(Mkamar::class, 'rs1', 'rs5');
    }

    public function kunjunganview()
    {
       return $this->hasMany(Kunjunganview::class, 'bpjskdruang', 'bpjskdruang');
    }
    public function kunjunganrs23()
    {
       return $this->hasMany(Kunjunganranap::class, 'rs6', 'rs1');
    }
    
}
