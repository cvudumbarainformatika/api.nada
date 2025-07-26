<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs98z';
    // rs1=kode, rs2=stok, rs3=harga, rs4=tempat, rs5=tanggal
}
