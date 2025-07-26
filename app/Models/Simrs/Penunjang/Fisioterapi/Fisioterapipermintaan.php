<?php

namespace App\Models\Simrs\Penunjang\Fisioterapi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fisioterapipermintaan extends Model
{
    use HasFactory;
    protected $table = 'rs201';
    protected $guarded = ['id'];
}
