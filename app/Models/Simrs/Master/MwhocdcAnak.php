<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Ews\MapingProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MwhocdcAnak extends Model
{
    use HasFactory;
    protected $table = 'm_who_cdc_anak';
    protected $guarded = ['id'];
}
