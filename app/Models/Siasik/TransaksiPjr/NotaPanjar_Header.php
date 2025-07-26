<?php

namespace App\Models\Siasik\TransaksiPjr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaPanjar_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'notapanjar_heder';
    public $timestamps = false;
    public function nota_rinci()
    {
        return $this->hasMany(NotaPanjar_Rinci::class, 'nonotapanjar', 'nonotapanjar');
    }
}
