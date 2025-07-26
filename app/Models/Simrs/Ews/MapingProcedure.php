<?php

namespace App\Models\Simrs\Ews;

use App\Models\Simrs\Master\Icd9prosedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapingProcedure extends Model
{
    use HasFactory;
    protected $table = 'prosedur_mapping';
    protected $guarded = ['id'];
    // public $timestamps = false;

    public function prosedur()
    {
        return $this->hasOne(Icd9prosedure::class, 'kd_prosedur', 'icd9');
    }
}
