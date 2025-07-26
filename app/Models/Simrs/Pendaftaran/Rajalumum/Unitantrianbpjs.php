<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unitantrianbpjs extends Model
{
    use HasFactory;
    protected $table = 'unit_antrian';
    protected $guarded = ['id'];
}
