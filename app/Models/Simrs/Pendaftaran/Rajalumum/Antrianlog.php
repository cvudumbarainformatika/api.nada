<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrianlog extends Model
{
    use HasFactory;
    protected $table = 'antrian_log';
    protected $guarded = ['id'];
}
