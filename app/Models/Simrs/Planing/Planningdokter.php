<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planningdokter extends Model
{
    use HasFactory;
    protected $table = 'planningdokter';
    protected $guarded = ['id'];
}
