<?php

namespace App\Models\Simrs\Penunjang\Endoscopy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Endoscopy extends Model
{
    use HasFactory;
    protected $table = 'rs246';
    protected $guarded = ['id'];

}
