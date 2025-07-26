<?php

namespace App\Models\Simrs\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsSpri extends Model
{
    use HasFactory;
    protected $table = 'bpjs_spri';
    protected $guarded = ['id'];
    // protected $timestamp = false;
}
