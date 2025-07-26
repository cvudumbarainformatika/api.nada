<?php

namespace App\Models\Arsip\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MmediaArsip extends Model
{
    use HasFactory;
    protected $table = 'master_media';
    protected $connection = 'arsip';
    protected $guarded = ['id'];
}
