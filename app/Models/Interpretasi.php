<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interpretasi extends Model
{
    use HasFactory;

    protected $table = 'rs215';

    protected $fillable =
    [
        'rs1', //noreg
        'rs2', //datetime
        'rs3', //interpretasi
        'rs4', // saran
        'rs5', // nota
        'ket', //ket
    ];
    public $timestamps = false;
}
