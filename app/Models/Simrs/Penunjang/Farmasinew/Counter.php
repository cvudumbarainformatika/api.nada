<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $table = 'conter';
    public $timestamps = false;

}
