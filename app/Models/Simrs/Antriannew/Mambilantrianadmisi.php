<?php

namespace App\Models\Simrs\Antriannew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mambilantrianadmisi extends Model
{
    use HasFactory;
    protected $table = 'max';
    protected $guarded = ['id'];
    protected $connection = 'newantrean';
}
