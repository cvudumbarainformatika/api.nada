<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mtindakanapheresis extends Model
{
    use HasFactory;
    protected $table = 'mtindkanapheresis';
    protected $guarded = ['id'];
    public $timestamps = false;
}
