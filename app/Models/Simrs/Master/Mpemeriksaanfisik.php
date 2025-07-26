<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpemeriksaanfisik extends Model
{
    use HasFactory;
    protected $table = 'mpemeriksaanfisik';
    protected $guarded = ['id'];

    public function gambars()
    {
        return $this->hasMany(Mtemplategambar::class);
    }
}
