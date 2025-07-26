<?php

namespace App\Models\Simrs\Antriannew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trcounterantrian extends Model
{
    use HasFactory;
    protected $table = 'conter_antrian';
    protected $guarded = ['id'];
    protected $connection = 'newantrean';
}
