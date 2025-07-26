<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeteranganKontrol extends Model
{
    use HasFactory;
    protected $table = 'mketeranganKontrol';
    protected $guarded = ['id'];
}
