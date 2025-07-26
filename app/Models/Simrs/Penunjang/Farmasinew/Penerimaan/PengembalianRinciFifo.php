<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengembalianRinciFifo extends Model
{
    // ini rincian pengembalian untuk mencatat obat mana yang dikembalikan sebagai pengganti yang di pinjam
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function header()
    {
        return $this->belongsTo(Pengembalian::class, 'nopengembalian', 'nopengembalian');
    }
    public function rinci()
    {
        return $this->belongsTo(Pengembalian::class, 'nopengembalian', 'nopengembalian');
    }
    public function rinci_penerimaan()
    {
        return $this->belongsTo(PenerimaanRinci::class, 'id_rincipenerimaan');
    }
}
