<?php

namespace App\Models\Simrs\Penunjang\Hemodialisa;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intradialitik extends Model
{
    /**
     * Field   Type          Null      Default  Comment
     * ------  ------------  ------    -------  ---------------
     * id      bigint(12)    NO        (NULL)
     * rs1     varchar(100)  YES       (NULL)   noreg
     * rs2     varchar(50)   YES       (NULL)   norm
     * rs3     datetime      YES       (NULL)   tgl
     * rs4     varchar(10)   YES       (NULL)   jam ke
     * rs5     varchar(255)  YES       (NULL)   keluhan
     * rs6     varchar(255)  YES       (NULL)   bb
     * rs7     varchar(255)  YES       (NULL)   kesadaran
     * rs8     varchar(255)  YES       (NULL)   tekanan darah
     * rs9     varchar(255)  YES       (NULL)   napas
     * rs10    varchar(255)  YES       (NULL)   suhu
     * rs11    varchar(255)  YES       (NULL)   qb
     * rs12    varchar(255)  YES       (NULL)   qd
     * rs13    varchar(255)  YES       (NULL)   tekanan vena
     * rs14    varchar(255)  YES       (NULL)   tmp
     * rs15    varchar(255)  YES       (NULL)   uf
     * rs16    varchar(255)  YES       (NULL)   assesment
     * rs17    varchar(255)  YES       (NULL)   perawat
     */
    use HasFactory;
    protected $table = 'rs264';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(Petugas::class, 'rs17', 'kdpegsimrs');
    }
}
