<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mplaning extends Model
{
    use HasFactory;
    protected $table = 'mplaning';
    protected $guarded = ['id'];
}
