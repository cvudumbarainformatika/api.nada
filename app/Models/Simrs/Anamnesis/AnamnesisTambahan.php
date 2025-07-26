<?php

namespace App\Models\Simrs\Anamnesis;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnamnesisTambahan extends Model
{
    use HasFactory;
    protected $table = 'rs209_igd_tambahan';
    protected $guarded = ['id'];
}
