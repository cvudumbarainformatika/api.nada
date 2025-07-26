<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mbahasa extends Model
{
    use HasFactory;
    protected $table = 'bahasa';
    protected $guarded = ['id'];
}
