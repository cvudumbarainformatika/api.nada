<?php

namespace App\Models\Simrs\Hais;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HaisTrans extends Model
{
    use HasFactory;

    protected $table = 'hais_trans';
    protected $guarded = ['id'];
    protected $connection = 'mysql';
    public $timestamps = false;
   
}
