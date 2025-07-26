<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokReal extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs98x';
    // rs1=kode, rs2=stok, rs3=harga, rs4=tempat
}
