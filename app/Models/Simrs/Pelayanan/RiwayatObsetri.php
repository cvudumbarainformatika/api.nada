<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatObsetri extends Model
{
    use HasFactory;
    protected $table = 'riwayatobsetri';
    protected $guarded = ['id'];

    public function pegawai()
    {
       return $this->belongsTo(Pegawai::class,'user_input', 'id');
    }
}
