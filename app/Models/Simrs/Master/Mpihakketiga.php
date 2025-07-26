<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpihakketiga extends Model
{
    use HasFactory;
    protected $table = 'pihak_ketiga';
    protected $guarded = ['id'];
    protected $connection = 'siasik';

    public function penerimaanobat()
    {
        return $this->hasMany(PenerimaanHeder::class,'kdpbf','kode');
    }

    public function penerimaanobatkonsinyasi()
    {
        return $this->hasMany(BastKonsinyasi::class,'kdpbf','kode');
    }

    public function penerimaanobatperiodeskrng()
    {
        return $this->hasMany(PenerimaanHeder::class,'kdpbf','kode');
    }

    public function penerimaanobatkonsinyasiperiodeskrng()
    {
        return $this->hasMany(BastKonsinyasi::class,'kdpbf','kode');
    }
}
