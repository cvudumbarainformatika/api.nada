<?php

namespace App\Models\Siasik\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekeningBank extends Model
{
    use HasFactory;
    // protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'keu_rekening_master';
    public $timestamps = false;
}
