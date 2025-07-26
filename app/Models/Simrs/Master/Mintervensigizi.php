<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mintervensigizi extends Model
{
    use HasFactory;
    protected $table = 'mintervensigizi';
    protected $guarded = ['id'];
}
