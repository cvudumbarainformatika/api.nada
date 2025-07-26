<?php

namespace App\Models\Pegawai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JabatanTambahan extends Model
{
    use HasFactory;

    protected $connection = 'kepex';
    protected $guarded = ['id'];
    protected $table = 'm_jabatan_tmb';
}
