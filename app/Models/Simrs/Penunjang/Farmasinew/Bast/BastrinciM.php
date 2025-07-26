<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Bast;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BastrinciM extends Model
{
    use HasFactory;
    protected $table = 'bast_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    public function masterobat(){
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
}
