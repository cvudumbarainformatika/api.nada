<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;
    protected $table = 'rs19';
    protected $guarded = ['rs1'];


    public function kunjungan_poli()
    {
        return $this->hasMany(KunjunganPoli::class, 'rs8', 'rs1');
    }
}
