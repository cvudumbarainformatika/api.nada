<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mdiagnosakeperawatan extends Model
{
    use HasFactory;
    protected $table = 'mdiagnosakeperawatan';
    protected $guarded = ['id'];

    public function intervensis()
    {
        return $this->hasMany(Mintervensikeperawatan::class, 'mdiagnosakeperawatan_kode', 'kode');
    }
}
