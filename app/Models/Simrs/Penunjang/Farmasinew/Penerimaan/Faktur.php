<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faktur extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $guarded = ['id'];
}
