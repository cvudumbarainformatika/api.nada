<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PelayananInformasiObat extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $guarded = ['id'];
    protected $casts = [
        'kode' => 'array',
    ];
}
