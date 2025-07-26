<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelaahResep extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $casts = [
        'administrasi' => 'array',
        'farmasi_klinis' => 'array',
        'komponen_resep' => 'array',
    ];

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'id', 'user_input');
    }
}
