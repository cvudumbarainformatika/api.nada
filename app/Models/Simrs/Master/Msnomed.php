<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Msnomed extends Model
{
    use HasFactory;
    protected $table = 'snomeds';
    protected $guarded = ['id'];
    public $timestamps = false;
 
}
