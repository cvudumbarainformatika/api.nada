<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mwilayah extends Model
{
    use HasFactory;
    protected $table = 'wilayah';
    protected $guarded = ['id'];
}
