<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapingObat extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'mapingobat';

    public function master()
    {
        return $this->belongsTo(MasterObat::class, 'obatlama', 'rs1');
    }
    public function stok()
    {
        return $this->hasMany(StokReal::class, 'rs1', 'obatlama');
    }
    public function stokopname()
    {
        return $this->hasMany(StokOpname::class, 'rs1', 'obatlama');
    }
    public function rincipenerimaan()
    {
        return $this->hasOne(TransaksiPenerimaanRinci::class, 'rs2', 'obatlama');
    }
}
