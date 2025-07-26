<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    use HasFactory;
    protected $table = 'perusahaan';

    protected $guarded =['id'];


    public function laborat_luar()
    {
       return $this->hasMany(LaboratLuar::class);
    }
}
