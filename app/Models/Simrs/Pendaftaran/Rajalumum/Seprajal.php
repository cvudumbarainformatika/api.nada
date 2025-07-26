<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seprajal extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'rs222';
    protected $guarded = ['id'];
    protected $appends = ['noref'];
    public $timestamps = false;

    protected $casts = [
        'Dinsos' => 'array',
        'prolanisPRB' => 'array',
        'noSKTM' => 'array',
    ];

    public function getNorefAttribute()
    {
        // if ($this->noDpjp === '') {
        //     $noref = $this->rs5;
        // }
        return $this->noDpjp ?? $this->rs5;
    }
}
