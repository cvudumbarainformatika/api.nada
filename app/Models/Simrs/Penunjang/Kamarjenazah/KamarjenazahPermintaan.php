<?php

namespace App\Models\Simrs\Penunjang\Kamarjenazah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KamarjenazahPermintaan extends Model
{
    use HasFactory;

    protected $table = 'rs274';
    protected $guarded = ['id'];
}
