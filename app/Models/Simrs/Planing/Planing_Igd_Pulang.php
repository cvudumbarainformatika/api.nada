<?php

namespace App\Models\Simrs\Planing;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planing_Igd_Pulang extends Model
{
    use HasFactory;
    protected $table = 'plann_igd_pulang';
    protected $guarded = ['id'];

    public function dokterpenangungjawabpulang()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'user_dokter');
    }
}
