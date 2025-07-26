<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mjknantrian extends Model
{
    use HasFactory;
    protected $table = 'bpjs_antrian';
    protected $guarded = ['id'];
}
