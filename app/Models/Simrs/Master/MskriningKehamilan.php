<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MskriningKehamilan extends Model
{
    use HasFactory;
    protected $table = 'm_skrining_kehamilan';
    protected $guarded = ['id'];
}
