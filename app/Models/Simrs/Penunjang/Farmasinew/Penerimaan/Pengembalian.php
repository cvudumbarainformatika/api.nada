<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Simrs\Master\Mpihakketiga;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengembalian extends Model
{
    // ini header pengembalian
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    public function rincian_fifo()
    {
        return $this->hasMany(PengembalianRinciFifo::class, 'nopengembalian', 'nopengembalian');
    }
    public function rincian()
    {
        return $this->hasMany(PengembalianRinci::class, 'nopengembalian', 'nopengembalian');
    }
    public function pihakketiga()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kdpbf');
    }
    public function penyedia()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kdpbf');
    }
}
