<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mpenilaian extends Model
{
    use HasFactory;
    protected $table = 'm_penilaians';
    protected $guarded = ['id'];
    protected $casts = [
      'form' => 'array',
      'grupings' => 'array'
  ];
}
