<?php

namespace App\Models\Simrs\Bpjs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bpjsrefpoli extends Model
{
    use HasFactory;
    protected $table = 'bpjs_ref_poli';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function scopeGetByKdSubspesialis($search, $req)
    {
        $search->when($req ?? false, function ($search, $query) {
            return $search->where('kdsubspesialis', $query);
        });
    }
}
