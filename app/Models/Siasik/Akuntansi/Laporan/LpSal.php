<?php

namespace App\Models\Siasik\Akuntansi\Laporan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LpSal extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'akuntansi_lpsal';
    public $timestamps = false;
}
