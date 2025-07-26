<?php

namespace App\Models\Siasik\TransaksiPendapatan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranKas extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'pengeluarankhaskecil';
    public $timestamps = false;
}
