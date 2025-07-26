<?php

namespace App\Models\Simrs\Edukasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transedukasi extends Model
{
    use HasFactory;
    protected $table = 'rs239';
    protected $guarded = ['id'];
}
