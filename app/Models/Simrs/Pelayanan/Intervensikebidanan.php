<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\Mintervensikebidanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervensikebidanan extends Model
{
    use HasFactory;
    protected $table = 'intervensikebidanan';
    protected $guarded = ['id'];

    public function masterintervensi()
    {
        return $this->belongsTo(Mintervensikebidanan::class, 'intervensi_id', 'id');
    }
}
