<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpraAnastesi extends Model
{
    use HasFactory;
    protected $table = 'm_pra_anastesi';
    protected $guarded = ['id'];
}
