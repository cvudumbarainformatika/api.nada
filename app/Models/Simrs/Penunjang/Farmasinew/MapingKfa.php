<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapingKfa extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $casts = [
        'response' => 'array',
        'dosage_form' => 'array',
        'active_ingredients' => 'array',
    ];
}
