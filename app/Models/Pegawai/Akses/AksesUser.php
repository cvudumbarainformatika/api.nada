<?php

namespace App\Models\Pegawai\Akses;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AksesUser extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aplikasi()
    {
        return $this->belongsTo(Aplikasi::class);
    }

    public function submenu()
    {
        return $this->belongsTo(Submenu::class);
    }
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
