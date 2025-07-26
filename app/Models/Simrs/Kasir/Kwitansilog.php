<?php

namespace App\Models\Simrs\Kasir;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kwitansilog extends Model
{
    use HasFactory;
    protected $table = 'kwitansilog';
    protected $guarded = ['id'];
    protected $connection = 'mysql';
}
