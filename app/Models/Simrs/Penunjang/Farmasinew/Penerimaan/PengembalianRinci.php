<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengembalianRinci extends Model
{
    // ini rincian permintaan pengembalian, tujuan nya mencatatkan rinci penerimaan mana yang akan di kembalikan
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function header()
    {
        return $this->belongsTo(Pengembalian::class, 'nopengembalian', 'nopengembalian');
    }
    public function rincian_fifo()
    {
        return $this->hasMany(PengembalianRinciFifo::class, 'nopengembalian', 'nopengembalian');
    }
    public function rinci_penerimaan()
    {
        return $this->belongsTo(PenerimaanRinci::class, 'id_rincipenerimaan');
    }
    public function masterobat()
    {
        return $this->belongsTo(Mobatnew::class, 'kdobat', 'kd_obat');
    }
    public function stok()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kdobat');
    }
}
