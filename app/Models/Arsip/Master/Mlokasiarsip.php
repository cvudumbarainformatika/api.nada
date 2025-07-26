<?php

namespace App\Models\Arsip\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mlokasiarsip extends Model
{
    use HasFactory;
    protected $table = 'master_lokasi';
    protected $connection = 'arsip';
    protected $guarded = ['id'];
}
