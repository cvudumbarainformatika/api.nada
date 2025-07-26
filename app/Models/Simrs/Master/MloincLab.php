<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MloincLab extends Model
{
    use HasFactory;
    protected $table = 'loinc_lab';
    protected $guarded = ['id'];
    public $timestamps = false;


 
}
