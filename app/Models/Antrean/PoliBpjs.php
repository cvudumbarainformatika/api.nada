<?php

namespace App\Models\Antrean;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoliBpjs extends Model
{
    use HasFactory;
    protected $connection = 'antrean';
    protected $table = 'poli_bpjs';
    protected $guarded = ['id'];
}
