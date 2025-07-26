<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mpendidikan extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'rs16';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];
}
