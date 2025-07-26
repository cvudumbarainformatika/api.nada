<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mwna extends Model
{
    use HasFactory;
    protected $table = 'rs15_wna';
    protected $guarded = ['id'];
}
