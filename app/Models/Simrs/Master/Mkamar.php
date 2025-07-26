<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mkamar extends Model
{
    use HasFactory;
    protected $table      = 'rs24';
    protected $guarded = ['id'];
    // protected $timestamp = false;

    public function kamars()
    {
        return $this->hasMany(MkamarRanap::class, 'rs6', 'groups'); //'rs25', 'rs24'
    }
    public function rinci_by_group()
    {
        return $this->hasMany(Mkamar::class, 'groups', 'groups'); //'rs25', 'rs24'
    }

    
}
