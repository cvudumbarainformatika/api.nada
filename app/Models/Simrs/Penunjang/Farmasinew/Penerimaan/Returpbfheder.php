<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Sigarang\Gudang;
use App\Models\Simrs\Master\Mpihakketiga;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returpbfheder extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $table = 'retur_penyedia_h';
    protected $guarded = ['id'];

    public function rinci()
    {
        return $this->hasMany(Returpbfrinci::class, 'no_retur', 'no_retur');
    }
    public function penyedia()
    {
        return $this->belongsTo(Mpihakketiga::class, 'kdpbf', 'kode');
    }
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang', 'kode');
    }
}
