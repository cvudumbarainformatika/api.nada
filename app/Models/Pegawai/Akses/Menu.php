<?php

namespace App\Models\Pegawai\Akses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function aplikasi()
    {
        return $this->belongsTo(Aplikasi::class);
    }

    public function submenus()
    {
        return $this->hasMany(Submenu::class);
    }
}
