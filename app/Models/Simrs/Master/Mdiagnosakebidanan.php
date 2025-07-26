<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mdiagnosakebidanan extends Model
{
    use HasFactory;
    protected $table = 'mdiagnosakebidanan';
    protected $guarded = ['id'];

    public function intervensis()
    {
        return $this->hasMany(Mintervensikebidanan::class, 'mdiagnosakebidanan_kode', 'kode');
    }
}
