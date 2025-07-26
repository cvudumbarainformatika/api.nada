<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturGudang extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function details()
    {
        return $this->hasMany(ReturGudangDetail::class, 'no_retur', 'no_retur');
    }
    public function depos()
    {
        return $this->belongsTo(Gudang::class, 'depo', 'kode');
    }
    public function gudangs()
    {
        return $this->belongsTo(Gudang::class, 'gudang', 'kode');
    }
    public function user()
    {
        return $this->belongsTo(Petugas::class, 'user_entry', 'kdpegsimrs');
    }
}
