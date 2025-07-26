<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Template;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Templateresep extends Model
{
    use HasFactory;
    protected $table = 'template_resep';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';


    public function rincian()
    {
        return $this->hasMany(TemplateResepRinci::class, 'template_id', 'id');
    }
}
