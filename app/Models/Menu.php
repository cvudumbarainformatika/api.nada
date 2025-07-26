<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function submenu()
    {
        return $this->hasMany(Submenu::class);
    }

    // this is a recommended way to declare event handlers
    // public static function boot()
    // {
    //     parent::boot();

    //     static::deleting(function ($menu) { // before delete() method call this
    //         $menu->submenu()->delete();
    //         // do the rest of the cleanup...
    //     });
    // }
}
