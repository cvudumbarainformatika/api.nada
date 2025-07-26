<?php

namespace App\Models\Simrs\Anamnesis;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnamnesisKebidanan extends Model
{
    use HasFactory;
    protected $table = 'anamnesis_kebidanan_igd';
    protected $guarded = ['id'];
}
