<?php

namespace App\Models\Siasik\TransaksiLS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notadinas_header extends Model
{
    use HasFactory;

    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'notadinas_heder';
    public $timestamps = false;

    public function rincians()
    {
        return $this->hasMany(Notadinas_rinci::class, 'nonotadinas', 'nonotadinas');
    }
}
