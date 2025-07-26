<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hutangpasien extends Model
{
    use HasFactory;
    protected $table = 'rs238';
    protected $guarded = ['id'];
    public $timestamps = false;
}
