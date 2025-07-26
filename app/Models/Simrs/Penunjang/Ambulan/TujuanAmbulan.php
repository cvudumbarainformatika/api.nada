<?php

namespace App\Models\Simrs\Penunjang\Ambulan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TujuanAmbulan extends Model
{
    use HasFactory;
    protected $table = 'rs281';
    protected $guarded = ['id'];
}
