<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MkuSnomed extends Model
{
    use HasFactory;
    protected $table = 'm_ku_snomed';
    protected $guarded = ['code'];
    public $timestamps = false;


 
}
