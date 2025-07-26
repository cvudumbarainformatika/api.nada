<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Bast;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpihakketiga;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BastKonsinyasi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rinci()
    {
        return $this->hasMany(DetailBastKonsinyasi::class, 'notranskonsi', 'notranskonsi');
    }
    public function penyedia()
    {
        return $this->belongsTo(Mpihakketiga::class, 'kdpbf', 'kode');
    }

    public function konsi()
    {
        return $this->belongsTo(Pegawai::class, 'user_konsi', 'kdpegsimrs');
    }
    public function bast()
    {
        return $this->belongsTo(Pegawai::class, 'user_bast', 'kdpegsimrs');
    }
    public function bayar()
    {
        return $this->belongsTo(Pegawai::class, 'user_bayar', 'kdpegsimrs');
    }
}
