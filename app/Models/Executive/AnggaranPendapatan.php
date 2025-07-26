<?php

namespace App\Models\Executive;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggaranPendapatan extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $table = 'anggaran_pendapatan';
    protected $guarded = ['id'];
}
