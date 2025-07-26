<?php

namespace App\Models\Simrs\Rajal;

use App\Models\Simrs\Master\Mpoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanKonsulPoli extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    // protected $casts = [
    //     'pertanyaan' => 'array',
    //     'jawaban' => 'array',
    // ];
    public function poliAsal()
    {
        return $this->belongsTo(Mpoli::class, 'poli_asal', 'rs1');
    }
    public function poliTujuan()
    {
        return $this->belongsTo(Mpoli::class, 'poli_tujuan', 'rs1');
    }
}
