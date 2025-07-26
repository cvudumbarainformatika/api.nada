<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Ews\MapingProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mwhocdcv2 extends Model
{
    use HasFactory;
    protected $table = 'm_who_cdcv2';
    protected $guarded = ['id'];
}
