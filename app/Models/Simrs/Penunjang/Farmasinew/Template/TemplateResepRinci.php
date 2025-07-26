<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Template;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateResepRinci extends Model
{
    use HasFactory;
    protected $table = 'template_resep_rinci';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function getRacikanAttribute($value){
        return $value === 1 ? true : false;
    }

    public function rincian()
    {
        return $this->hasMany(TemplateResepRacikan::class, 'obat_id', 'id');
    }
}
