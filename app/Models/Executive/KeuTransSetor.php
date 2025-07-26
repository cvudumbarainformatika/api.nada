<?php

namespace App\Models\Executive;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeuTransSetor extends Model
{
    use HasFactory;
    // protected $connection = 'kepex';
    protected $table = 'keu_trans_setor';
    protected $guarded = ['id'];
}
