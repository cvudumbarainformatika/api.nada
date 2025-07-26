<?php

namespace App\Models\Simrs\Pendaftaran;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karcispoli extends Model
{
    use HasFactory;
    protected $table = 'rs35';
    protected $guarded = ['id'];
    public $timestamps = false;
}
