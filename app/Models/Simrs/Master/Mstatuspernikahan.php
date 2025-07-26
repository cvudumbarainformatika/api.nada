<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mstatuspernikahan extends Model
{
    use HasFactory;
    protected $table = 'statuspernikahan';
    protected $guarded = ['id'];
}
