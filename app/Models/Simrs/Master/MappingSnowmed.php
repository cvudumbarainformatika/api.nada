<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappingSnowmed extends Model
{
    use HasFactory;
    protected $table = 'mapping_snowmed';
    protected $guarded = ['id'];
}
