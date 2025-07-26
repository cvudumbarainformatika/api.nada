<?php

namespace App\Models\Simrs\Anamnesis;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnamnesisBps extends Model
{
    use HasFactory;
    protected $table = 'rs209_igd_bps';
    protected $guarded = ['id'];
}
