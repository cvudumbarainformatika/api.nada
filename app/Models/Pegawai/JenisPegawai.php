<?php

namespace App\Models\Pegawai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisPegawai extends Model
{
    use HasFactory;
    protected $connection = 'kepex';

    protected $table = 'm_jenispegawai';

    protected $guarded = ['id'];
}
