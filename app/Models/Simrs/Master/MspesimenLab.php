<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MspesimenLab extends Model
{
    use HasFactory;
    protected $table = 'rs49_spesimen';
    protected $guarded = ['id'];
}
