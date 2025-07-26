<?php

namespace App\Models\Siasik\Master;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akun_mapjurnal extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'akun_mapjurnal';
    public $timestamps = false;

    public function newMasterobat()
    {
        return $this->belongsTo(Mobatnew::class, 'kode50', 'kodeall');
    }
}
