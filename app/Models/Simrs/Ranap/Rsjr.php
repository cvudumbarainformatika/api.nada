<?php

namespace App\Models\Simrs\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rsjr extends Model
{
    use HasFactory;
    protected $table = 'rsjr';
    protected $guarded = ['id'];
    // protected $timestamp = false;
}
