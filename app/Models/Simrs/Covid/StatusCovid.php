<?php

namespace App\Models\Simrs\Covid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusCovid extends Model
{
    use HasFactory;
    protected $table = 'tflag_covid';
    protected $guarded = ['id'];

    public $timestamps = false;
}
