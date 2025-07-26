<?php

namespace App\Models\Arsip\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MkelasifikasiArsip extends Model
{
    use HasFactory;
    protected $table = 'master_kode';
    protected $connection = 'arsip';
    protected $guarded = ['id'];
}
