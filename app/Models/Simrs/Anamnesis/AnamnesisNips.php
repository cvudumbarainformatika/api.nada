<?php

namespace App\Models\Simrs\Anamnesis;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnamnesisNips extends Model
{
    use HasFactory;
    protected $table = 'rs209_igd_nips';
    protected $guarded = ['id'];
}
