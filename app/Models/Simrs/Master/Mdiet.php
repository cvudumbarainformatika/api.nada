<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mdiet extends Model
{
    use HasFactory;
    protected $table = 'rs14';
    protected $guarded = ['id'];
}
