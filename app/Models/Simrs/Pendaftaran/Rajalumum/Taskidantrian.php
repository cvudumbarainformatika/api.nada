<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taskidantrian extends Model
{
    use HasFactory;
    protected $table = 'bpjs_respon_time';
    protected $guarded = ['id'];
}
