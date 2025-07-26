<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Generalconsenttrans_h extends Model
{
    use HasFactory;
    protected $table = 'generalconsent';
    protected $guarded = ['id'];

    public function hederrinci()
    {
        return $this->hasMany(Generalconsenttrans_r::class);
    }
}
