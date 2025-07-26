<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuanganRawatInap extends Model
{
    use HasFactory;
    protected $table = 'rs24';

    protected $connection = 'mysql';
}
