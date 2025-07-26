<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mhais extends Model
{
    use HasFactory;
    protected $table = 'hais';
    protected $guarded = ['id'];
    public $timestamps = false;
}
