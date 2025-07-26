<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rstigapuluhtarif extends Model
{
    use HasFactory;
    protected $table = 'rs30tarif';
    protected $gurded = ['id'];
    public $timestamps = false;
}
