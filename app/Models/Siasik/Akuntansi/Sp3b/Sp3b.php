<?php

namespace App\Models\Siasik\Akuntansi\Sp3b;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sp3b extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'sp3b';
    public $timestamps = false;

    public function rincians()
    {
        return $this->hasMany(Sp3b_rinci::class, 'nosp3b', 'nosp3b');
    }
}
