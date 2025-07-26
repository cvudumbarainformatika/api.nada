<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mjenisperbekalan extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $connection = 'farmasi';
    protected $table = 'mjenisperbekalan';
    protected $guarded = ['id'];
}
