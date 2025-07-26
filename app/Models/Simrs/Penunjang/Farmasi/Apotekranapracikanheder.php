<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use App\Models\Simrs\Master\Mobat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekranapracikanheder extends Model
{
    use HasFactory;
    protected $table = 'rs39';
    protected $guarded = ['id'];

    public function apotekranapracikanrinci()
    {
        return $this->hasMany(Apotekranapracikanrinci::class, 'rs1', 'rs1');
    }

    public function masterobat()
    {
        return $this->hasOne(Mobat::class, 'rs1', 'rs4');
    }
}
