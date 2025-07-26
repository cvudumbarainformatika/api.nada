<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeonatusMedis extends Model
{
    use HasFactory;
    protected $table = 'neonatusmedis';
    protected $guarded = ['id'];

    public function pegawai()
    {
       return $this->belongsTo(Pegawai::class,'user_input', 'id');
    }

    public function riwayatkehamilan()
    {
       return $this->hasMany(RiwayatKehamilan::class, 'norm','norm');
    } 
}
