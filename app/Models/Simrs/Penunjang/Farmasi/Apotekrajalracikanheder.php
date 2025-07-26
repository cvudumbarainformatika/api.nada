<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekrajalracikanheder extends Model
{
    use HasFactory;
    protected $table = 'rs91';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function racikanhederrinci()
    {
        return $this->hasMany(Apotekrajalracikanrinci::class, 'rs1', 'rs1');
    }
}
