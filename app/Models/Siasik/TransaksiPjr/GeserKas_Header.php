<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeserKas_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'pergeseranTheder';
    public $timestamps = false;
    public function kasrinci()
    {
        return $this->hasMany(GeserKas_Rinci::class, 'notrans', 'notrans');
    }
}
