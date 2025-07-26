<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekrajalracikanhedlalu extends Model
{
    use HasFactory;
    protected $table = 'rs163';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function racikanhederrinci()
    {
        return $this->hasMany(Apotekrajalracikanrincilalu::class, 'rs1', 'rs1');
    }
}
