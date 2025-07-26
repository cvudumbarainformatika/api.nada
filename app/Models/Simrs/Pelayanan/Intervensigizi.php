<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\Mintervensigizi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervensigizi extends Model
{
    use HasFactory;
    protected $table = 'intervensigizi';
    protected $guarded = ['id'];

    public function masterintervensi()
    {
        return $this->belongsTo(Mintervensigizi::class, 'intervensi_id', 'id');
    }
}
