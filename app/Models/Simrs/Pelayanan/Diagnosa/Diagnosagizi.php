<?php

namespace App\Models\Simrs\Pelayanan\Diagnosa;

use App\Models\Simrs\Pelayanan\Intervensigizi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosagizi extends Model
{
    use HasFactory;
    protected $table = 'diagnosagizi';
    protected $guarded = ['id'];

    public function intervensi()
    {
        return $this->hasMany(Intervensigizi::class, 'diagnosagizi_kode', 'id');
    }
}