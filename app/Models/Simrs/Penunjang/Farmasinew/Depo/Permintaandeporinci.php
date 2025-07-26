<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Permintaandeporinci extends Model
{
    use HasFactory;
    protected $table = 'permintaan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function permintaanobatheder()
    {
        return $this->hasOne(Permintaandepoheder::class, 'no_permintaan', 'no_permintaan');
    }

    public function masterobat()
    {
        return $this->HasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }

    public function stokreal()
    {
        return $this->hasMany(Stokrel::class, 'kdobat', 'kdobat');
    }

    // ini salah
    // public function mutasi()
    // {
    //     return $this->hasOne(Mutasigudangkedepo::class, ['no_permintaan', 'kdobat'], ['no_permintaan', 'kd_sobat']);
    // }

    public function mutasi()
    {
        return $this->hasMany(Mutasigudangkedepo::class, 'no_permintaan', 'no_permintaan');
    }
}
