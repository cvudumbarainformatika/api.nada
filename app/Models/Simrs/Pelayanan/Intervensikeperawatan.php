<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\Mintervensikeperawatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervensikeperawatan extends Model
{
    use HasFactory;
    protected $table = 'intervensikeperawatan';
    protected $guarded = ['id'];

    public function masterintervensi()
    {
        return $this->belongsTo(Mintervensikeperawatan::class, 'intervensi_id', 'id');
    }
}
