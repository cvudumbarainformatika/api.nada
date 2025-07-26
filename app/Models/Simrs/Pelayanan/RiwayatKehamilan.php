<?php

namespace App\Models\Simrs\Pelayanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatKehamilan extends Model
{
    use HasFactory;
    protected $table = 'riwayatkehamilan';
    protected $guarded = ['id'];
}
