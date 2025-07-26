<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiPenerimaan extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs81';
    /**
     * rs1=nomor
     * rs2=tanggal
     */
}
