<?php

namespace App\Models\Simrs\Rajal\Igd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tinjauan_ulang_bps extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'peninjauan_ulang_igd_bps';
    protected $guarded = ['id'];
}
