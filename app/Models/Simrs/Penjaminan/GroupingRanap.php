<?php

namespace App\Models\Simrs\Penjaminan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupingRanap extends Model
{
    use HasFactory;
    protected $table = 'grouping_klaim_ranap';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $cbg_tarif = $this->cbg_tarif;
        $procedure_tarif = $this->procedure_tarif;
        $prosthesis_tarif = $this->prosthesis_tarif;
        $investigation_tarif = $this->investigation_tarif;
        $drug_tarif = $this->drug_tarif;
        $acute_tarif = $this->acute_tarif;
        $chronic_tarif = $this->chronic_tarif;
        $subtotal = ($cbg_tarif+$procedure_tarif+$prosthesis_tarif+$investigation_tarif+$drug_tarif+$acute_tarif+$chronic_tarif);
        return ($subtotal);
    }
}
