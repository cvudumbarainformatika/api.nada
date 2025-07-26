<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mdiagnosagizi extends Model
{
    use HasFactory;
    protected $table = 'mdiagnosagizi';
    protected $guarded = ['id'];

    public function intervensis()
    {
        return $this->hasMany(Mintervensigizi::class, 'mdiagnosagizi_kode', 'kode');
    }
}
