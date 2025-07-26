<?php

namespace App\Models\Simrs\Pelayanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanTindakan extends Model
{
    use HasFactory;
    protected $table = 'laporan_tindakan';
    protected $guarded = ['id'];
    // protected $casts = [
    //   'laporantindakan' => 'array',
    // ];
}
