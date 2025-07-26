<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusPasienPoli extends Model
{
    use HasFactory;
    protected $table = 'rs141';

    public function kunjungan_poli()
    {
        return $this->belongsTo(KunjunganPoli::class, 'rs1', 'rs1');
    }
}
