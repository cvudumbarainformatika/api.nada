<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use App\Models\Simrs\Master\Mobat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekrajalracikanrincilalu extends Model
{
    use HasFactory;
    protected $table = 'rs164';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';
    protected $appends = ['subtotal'];

    public function racikanrinci()
    {
        return $this->belongsTo(Mobat::class, 'rs4', 'rs1');
    }

    public function masterobat()
    {
        return $this->hasOne(Mobat::class, 'rs1', 'rs4');
    }

    public function relasihederracikan()
    {
        return $this->belongsTo(Apotekrajalracikanhedlalu::class, 'rs1', 'rs1');
    }

    public function getSubtotalAttribute()
    {
        $harga = $this->rs7;
        $jumlah = $this->rs5;
        return ($harga * $jumlah);
    }
}
