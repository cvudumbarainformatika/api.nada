<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plann_Igd_Ranap_Ruang extends Model
{
    use HasFactory;
    protected $table = 'plann_igd_ranap_ruang';
    protected $guarded = ['id'];
    protected $casts = [
             'isi' => 'array'
         ];
}
