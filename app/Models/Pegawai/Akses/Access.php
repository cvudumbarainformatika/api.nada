<?php

namespace App\Models\Pegawai\Akses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function aplikasi()
    {
        return $this->belongsTo(Aplikasi::class);
    }

    public function submenu()
    {
        return $this->belongsTo(Submenu::class);
    }
    public function menus()
    {
        return $this->belongsTo(Menu::class);
    }
}
