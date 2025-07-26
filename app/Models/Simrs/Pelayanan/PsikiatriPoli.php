<?php

namespace App\Models\Simrs\Pelayanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsikiatriPoli extends Model
{
    use HasFactory;
    protected $table = 'psikiatripoli';
    protected $guarded = ['id'];
    protected $casts = [
      'psikotespendukung' => 'array',
      'simptom' => 'array',
  ];
}
