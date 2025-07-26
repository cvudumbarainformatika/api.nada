<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaandepoheder extends Model
{
    use HasFactory;
    protected $table = 'permintaan_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function permintaanrinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'no_permintaan', 'no_permintaan');
    }

    public function user()
    {
        return $this->hasOne(Pegawai::class, 'id', 'user');
    }
    public function ruangan()
    {
        return $this->belongsTo(Ruang::class, 'dari', 'kode');
    }
    public function asal()
    {
        return $this->belongsTo(Gudang::class, 'dari', 'kode');
    }
    public function menuju()
    {
        return $this->belongsTo(Gudang::class, 'tujuan', 'kode');
    }

    public function mutasigudangkedepo()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'no_permintaan', 'no_permintaan');
    }
}
