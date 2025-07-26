<?php

namespace App\Models\Simrs\Tindakan;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Ews\MapingProcedure;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gbrdokumentindakan extends Model
{
    use HasFactory;
    protected $table = 'gbrdoktindakans';
    protected $guarded = ['id'];

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class);
    }
}
