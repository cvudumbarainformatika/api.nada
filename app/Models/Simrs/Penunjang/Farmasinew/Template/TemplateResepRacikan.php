<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Template;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateResepRacikan extends Model
{
    use HasFactory;
    protected $table = 'template_resep_racikan';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
