<?php

namespace App\Models\Simrs\Ranap\Views;

use App\Models\Simrs\Master\MkamarRanap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjunganview extends Model
{
    use HasFactory;
    protected $table = 'v_15_23';
    protected $connection = 'mysql';

    // public function beds()
    // {
    //    return $this->belongsToMany(MkamarRanap::class, 'kd_kmr', 'rs1');
    // }
}
