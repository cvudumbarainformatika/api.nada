<?php

namespace App\Models\Simrs\Kasir;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kwitansidetail extends Model
{
    use HasFactory;
    protected $table = 'kwitansi_d';
    protected $guarded = ['id'];

    public $timestamps = false;
}
