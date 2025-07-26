<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MorganisasiAdministrasi extends Model
{
    use HasFactory;
    protected $table = 'organisasi';
    protected $connection = 'siasik';
    protected $guarded = ['id'];
}
