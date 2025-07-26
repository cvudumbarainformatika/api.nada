<?php

namespace App\Models\Simrs\Penunjang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DietTrans extends Model
{
    use HasFactory;
    protected $table = 'diet';
    protected $guarded = ['id'];
}
