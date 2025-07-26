<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mjeniskartukarcis extends Model
{
    use HasFactory;
    protected $table = 'mjeniskarcis';
    protected $guarded = ['id'];
}
