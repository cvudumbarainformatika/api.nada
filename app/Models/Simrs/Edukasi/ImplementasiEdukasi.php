<?php

namespace App\Models\Simrs\Edukasi;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImplementasiEdukasi extends Model
{
    use HasFactory;
    protected $table = 'rs239_implementasi';
    protected $guarded = ['id'];

    protected $casts = [
      'metode' => 'array',
      'materi' => 'array',
      'media' => 'array',
    ];


    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs', 'user');
    }
}
