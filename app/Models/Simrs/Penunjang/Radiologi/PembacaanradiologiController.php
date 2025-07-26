<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembacaanradiologiController extends Model
{
    use HasFactory;
    protected $table = 'rs151';
    protected $guarded = ['id'];
}
