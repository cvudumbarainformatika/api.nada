<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiPenerimaanRinci extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs82';
    /**
     * rs1=nomor
     * rs2=kode obat
     * rs6=nobatch
     * rs7=tgl kadalwarsa
     */
    public function header()
    {
        return $this->belongsTo(TransaksiPenerimaan::class, 'rs1', 'rs1');
    }
}
