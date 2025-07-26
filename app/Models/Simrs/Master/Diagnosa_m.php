<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosa_m extends Model
{
    use HasFactory;
    protected $table = 'rs99x';
    protected $guarded = ['id'];
    protected $connection = 'mysql';
}
