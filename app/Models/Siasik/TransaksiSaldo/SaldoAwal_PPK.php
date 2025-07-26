<?php

namespace App\Models\Siasik\TransaksiSaldo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaldoAwal_PPK extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'saldoawal_ppk';
    public $timestamps = false;
}
