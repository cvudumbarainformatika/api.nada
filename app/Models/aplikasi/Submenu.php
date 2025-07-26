<?php

namespace App\Models\aplikasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submenu extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    // public function submenus()
    // {
    //     return $this->hasMany(Sub::class);
    // }
}
