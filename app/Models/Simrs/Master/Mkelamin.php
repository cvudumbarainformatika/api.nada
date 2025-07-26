<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mkelamin extends Model
{
    use HasFactory;
    protected $table = 'kelamin';
    protected $guarded = ['id'];
}
