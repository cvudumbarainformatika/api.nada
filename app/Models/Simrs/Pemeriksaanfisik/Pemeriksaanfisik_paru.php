<?php

namespace App\Models\Simrs\Pemeriksaanfisik;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemeriksaanfisik_paru extends Model
{
    use HasFactory;
    protected $table = 'pemeriksaanfisik_paru';
    protected $guarded = ['id'];
}
