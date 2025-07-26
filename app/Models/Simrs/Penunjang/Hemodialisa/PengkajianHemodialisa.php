<?php

namespace App\Models\Simrs\Penunjang\Hemodialisa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengkajianHemodialisa extends Model
{
    /**
     *
     * Table: rs265
     * ----------------

     * Column Information*

     * Field   Type          Null    Default              Comment
     * ------  ------------  ------  -------------------  -------------
     * id      bigint(12)    NO      (NULL)               id
     * rs1     varchar(100)  YES     (NULL)               noreg
     * rs2     varchar(50)   YES     (NULL)               norm
     * rs3     datetime      YES     0000-00-00 00:00:00  tgl
     * rs4     varchar(255)  YES     (NULL)               alasan
     * rs5     varchar(255)  YES     (NULL)               riwayat
     * rs6     varchar(255)  YES     (NULL)               hubungan
     * rs7     varchar(255)  YES     (NULL)               psokologis
     * rs8     varchar(255)  YES     (NULL)               lain
     * rs9     varchar(255)  YES     (NULL)               td
     * rs10    varchar(255)  YES     (NULL)               nadi
     * rs11    varchar(255)  YES     (NULL)               suhu
     * rs12    varchar(255)  YES     (NULL)               tb
     * rs13    varchar(255)  YES     (NULL)               bb
     * rs14    varchar(255)  YES     (NULL)               parameter / nafsu makan
     * rs15    varchar(255)  YES     (NULL)               parameterx /  diagnosa khusus
     * rs16    varchar(255)  YES     (NULL)               parameterxx / status fungsional
     * rs17    varchar(255)  YES     (NULL)               fungsional
     * rs18    varchar(255)  YES     (NULL)               lainx
     *
     */
    use HasFactory;
    protected $table = 'rs265';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'resiko_jatuh' => 'array',
    ];
}
