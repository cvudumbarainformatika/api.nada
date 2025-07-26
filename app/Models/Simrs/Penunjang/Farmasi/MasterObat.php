<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterObat extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs32';
    // rs1=kode, rs2=nama, rs3=tipe, rs4=harga
}
