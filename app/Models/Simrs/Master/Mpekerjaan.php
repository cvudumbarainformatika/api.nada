<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpekerjaan extends Model
{
    use HasFactory;
    protected $table = 'mpekerjaan';
    protected $guarded = ['id'];
}
