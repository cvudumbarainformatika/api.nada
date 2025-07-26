<?php

namespace App\Models\Simrs\Kasir;

use App\Models\KunjunganRawatInap;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adminranap extends Model
{
    use HasFactory;
    protected $table = 'rs23_admin';
    protected $guarded = ['id'];


}
