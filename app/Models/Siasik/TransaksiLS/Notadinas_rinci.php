<?php

namespace App\Models\Siasik\TransaksiLS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notadinas_rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'notadinas_rinci';
    public $timestamps = false;

    public function npdlsrinci()
    {
        return $this->hasMany(NpdLS_rinci::class, 'nonpdls', 'nonpdls');
    }
}
