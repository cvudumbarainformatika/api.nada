<?php

namespace App\Models\Simrs\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rs141 extends Model
{
    use HasFactory;
    protected $table = 'rs141';
    protected $guarded = ['id'];
    // protected $timestamp = false;

    public function kunjungan_ranap()
    {
       return $this->hasOne(Kunjunganranap::class, 'rs1', 'flag');
    }
}
