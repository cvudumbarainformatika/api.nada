<?php

namespace App\Models\Pegawai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpegawaisimpeg extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $table = 'pegawai';
    protected $guarded = ['id'];
}
