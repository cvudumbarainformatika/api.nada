<?php

namespace App\Models\Simrs\Pelayanan\Diagnosa;

use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Pelayanan\Intervensikeperawatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosakeperawatan extends Model
{
    use HasFactory;
    protected $table = 'diagnosakeperawatan';
    protected $guarded = ['id'];
    // protected $connection = 'kepex';

    public function intervensi()
    {
        return $this->hasMany(Intervensikeperawatan::class, 'diagnosakeperawatan_kode', 'id');
    }

    public function masterperawat()
    {
        return $this->hasOne(Pegawai::class, 'id', 'user_input');
    }
    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'id', 'user_input');
    }
}
