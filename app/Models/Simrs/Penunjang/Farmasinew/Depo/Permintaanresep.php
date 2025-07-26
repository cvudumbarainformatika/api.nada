<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Msigna;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaanresep extends Model
{
    use HasFactory;
    protected $table = 'resep_permintaan_keluar';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function mobat()
    {
        return $this->hasone(Mobatnew::class, 'kd_obat', 'kdobat');
    }
    public function stok()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kdobat');
    }

    public function aturansigna()
    {
        return $this->belongsTo(Msigna::class, 'aturan', 'signa');
    }
    public function head()
    {
        $this->belongsTo(Resepkeluarheder::class, 'noresep', 'noresep');
    }
}
