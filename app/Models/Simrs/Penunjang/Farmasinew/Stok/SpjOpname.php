<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Stok;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpjOpname extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $casts = [
        'pelaksanas' => 'array'
    ];
}
