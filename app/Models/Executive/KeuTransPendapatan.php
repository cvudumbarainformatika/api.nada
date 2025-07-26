<?php

namespace App\Models\Executive;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeuTransPendapatan extends Model
{
    use HasFactory;
    // protected $connection = 'kepex';
    protected $table = 'keu_trans_pendapatan';
    protected $guarded = ['id'];
}
