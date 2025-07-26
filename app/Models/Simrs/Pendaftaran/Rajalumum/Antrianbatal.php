<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrianbatal extends Model
{
    use HasFactory;
    protected $table = 'antrian_batal';
    protected $guarded = ['id'];
}
