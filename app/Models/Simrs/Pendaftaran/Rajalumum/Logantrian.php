<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logantrian extends Model
{
    use HasFactory;
    protected $table = 'log_antrian';
    protected $guarded = ['id'];
    public $timestamps = false;
}
