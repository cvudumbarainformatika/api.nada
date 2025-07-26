<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Icd9prosedure extends Model
{
    use HasFactory;
    protected $table = 'prosedur_master';
    protected $guarded = ['id'];
}
