<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kandungan extends Model
{
    use HasFactory;
    protected $table = 'kandungan';
    protected $guarded = ['id'];

    protected $casts = [
      'resikoJatuh' => 'array',
    ];

    public function pegawai()
    {
       return $this->belongsTo(Pegawai::class,'user_input', 'id');
    }
}
