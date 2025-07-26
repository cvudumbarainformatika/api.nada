<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekranaplaluracikanheder extends Model
{
    use HasFactory;
    protected $table = 'rs63';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function apotekranapracikanrincilalu()
    {
        return $this->hasMany(Apotekranaplaluracikanrinci::class, 'rs1', 'rs1');
    }
}
