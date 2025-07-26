<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Magama extends Model
{
    use HasFactory;
    protected $table = 'rs12';
    protected $guarded = ['id'];
}
