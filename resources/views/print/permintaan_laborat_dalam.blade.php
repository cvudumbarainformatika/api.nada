<!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
    <link rel="stylesheet" type="text/css" media="print" href="{{ URL::asset('print') }}/mystyles.scss">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('styles') }}/main.scss">
    <title></title>
</head>

<body topmargin="0" leftmargin="0" rightmargin="0" class="f-12">
    <div class="page">
        <div class="row">
            <div class="">
                <img class="logo" src="{{ URL::asset('images') }}/logo-rsud.png" alt="logo-kota-grayscale" style="width:70px;" />
            </div>
            <div class="mt-10 ml-10">
                <div class="title bold">{{ $header->title }}</div>
                <div class="subtitle">{{ $header->sub }}</div>
                <div class="subtitle">{{ $header->sub2 }}</div>
            </div>
        </div>
        <hr />

        <!-- header -->
        <div class="title bold text-center" style="margin-bottom:10px">HASIL PERMINTAAN LABORATORIUM</div>
        <hr />
        <!-- <div class="title mb-10 italic text-center">LABORATORY EXAMINATION RESULTS </div> -->
        <?php
        $pasien = $details[0]->poli ? $details[0]->pasien_kunjungan_poli : $details[0]->pasien_kunjungan_rawat_inap;
        $tgl_selesai = date('Y-m-d', strtotime($details[0]->rs29));
        $jam_selesai = date('H:i:s', strtotime($details[0]->rs29));
        function hitung_umur($tanggal_lahir)
        {
            $birthDate = new DateTime($tanggal_lahir);
            $today = new DateTime();
            if ($birthDate > $today) {
                exit("0 tahun 0 bulan 0 hari");
            }
            $y = $today->diff($birthDate)->y;
            $m = $today->diff($birthDate)->m;
            $d = $today->diff($birthDate)->d;
            return $y . " Thn " . $m . " Bln " . $d . " Hr";
        }

        $ruangan = $details[0]->poli ? $details[0]->poli->rs2 : $details[0]->kunjungan_rawat_inap->ruangan->rs2;
        $sistemBayar = $details[0]->poli ? $details[0]->sb_kunjungan_poli->rs2 : $details[0]->sb_kunjungan_rawat_inap->rs2;
        $dokter = $details[0]->dokter ? $details[0]->dokter->rs2 : '-';
        $tanggal_permintaan = $details[0]->tanggal;
        $tanggal_validasi = $details[0]->rs29;
        ?>
        <!-- Detail Pasien -->
        <div class="column" style="margin-top:10px; margin-bottom:10px;">
            <div class="row justify-between">
                <div class="left">
                    <div class="row">
                        <div style="width:100px">Nama</div>
                        <div>: {{$pasien->rs2}}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">tgl Lahir / Umur</div>
                        <div>: {{ date('d-m-Y', strtotime($pasien->rs16)) }} / {{hitung_umur($pasien->rs16)}}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">No Reg</div>
                        <div>: {{ $details[0]->rs1 }}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">No RM</div>
                        <div>: {{ $pasien->rs1 }}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">Alamat</div>
                        <div>: {{ $pasien->rs4 }}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">Ruangan / Poli</div>
                        <div>: {{ $ruangan }}</div>
                    </div>

                </div>
                <div class="right">
                    <div class="row">
                        <div style="width:100px">Atas Permintaan </div>
                        <div style="width:180px">: {{ $dokter }}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">Tgl Permintaan </div>
                        <div>: {{$tanggal_permintaan}}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">Tgl Validasi </div>
                        <div>: {{ $tanggal_validasi }}</div>
                    </div>
                    <div class="row">
                        <div style="width:100px">TAT </div>
                        <div>: {{$details[0]->tat}}</div>
                    </div>
                    <!-- <div class="row">
                        <div>TAT Permintaan </div>
                        <div>: {{$details[0]->poli? $details[0]->sb_kunjungan_poli->rs2:$details[0]->sb_kunjungan_rawat_inap->rs2}}</div>
                    </div> -->
                    <div class="row">
                        <div style="width:100px">Metode Bayar </div>
                        <div>: {{$sistemBayar}}</div>
                    </div>
                </div>
            </div>
        </div>
        <hr />

        <?php $gg = collect($details)->groupBy('pemeriksaan_laborat.rs21')->toArray(); ?>


        <table width="100%" class="table" cellpadding="0" cellspacing="0" border="1" bordercolor="#006699" bordercolordark="#666666" bordercolorlight="#003399">
            <thead>
                <tr valign="middle" style="border-bottom: solid 1px rgb(190, 190, 190);">
                    <td><b>PEMERIKSAAN</b></td>
                    <td width="5%"></td>
                    <td><b>HASIL</b></td>
                    <td><b>NILAI NORMAL</b></td>
                    <td><b>SATUAN</b></td>
                    <td><b>METODE</b></td>
                </tr>
            </thead>
            <tbody class="f-12">
                <?php $i = 1;
                $total = 0;
                $x = 1;
                $no = 1;
                foreach ($gg as $key => $values) { ?>
                    <?php
                    for ($n = 0; $n < count($values); $n++) {
                    ?>
                        <?php if ($values[$n]['pemeriksaan_laborat']['rs21'] === '') {
                            $total +=  $values[$n]['subtotal'];
                            $x = $n;
                            $no = $i + $n;

                        ?>
                            <tr>
                                <td> {{ $values[$n]['pemeriksaan_laborat']['rs2'] }} </td>
                                <td class="<?= $values[$n]['flag'] ? 'redColor' : ''; ?>"> {{ $values[$n]['flag']}} </td>
                                <td class="<?= $values[$n]['flag'] ? 'redColor' : ''; ?>"> {{ $values[$n]['rs21']}} </td>
                                <td> {{ $values[$n]['pemeriksaan_laborat']['nilainormal']}} </td>
                                <td> {{ $values[$n]['pemeriksaan_laborat']['satuan']}} </td>
                                <td> {{ $values[$n]['metode'] }} </td>
                            </tr>
                        <?php } elseif ($values[0]['pemeriksaan_laborat']['rs21'] !== '' && $n === 0) {
                            $total +=  $values[0]['subtotal'];
                        ?>
                            <tr>
                                <td> {{ $values[0]['pemeriksaan_laborat']['rs21'] }} </td>
                                <td> </td>
                                <td> </td>
                                <td> </td>
                                <td> </td>

                            </tr>
                            <tr class="list">
                                <td> - {{ $values[0]['pemeriksaan_laborat']['rs2'] }} </td>
                                <td class="<?= $values[0]['flag'] ? 'redColor' : ''; ?>"> {{ $values[0]['flag'] }} </td>
                                <td class="<?= $values[0]['flag'] ? 'redColor' : ''; ?>"> {{ $values[0]['rs21']}} </td>
                                <td> {{ $values[0]['pemeriksaan_laborat']['nilainormal']}} </td>
                                <td> {{ $values[0]['pemeriksaan_laborat']['satuan']}} </td>
                                <td> {{ $values[0]['metode'] }} </td>
                            </tr>
                        <?php } else {
                        ?>
                            <tr class="list">
                                <td> - {{ $values[$n]['pemeriksaan_laborat']['rs2'] }} </td>
                                <td class="<?= $values[$n]['flag'] ? 'redColor' : ''; ?>"> {{ $values[$n]['flag'] }}</td>
                                <td class="<?= $values[$n]['flag'] ? 'redColor' : ''; ?>"> {{ $values[$n]['rs21'] }} </td>
                                <td> {{ $values[$n]['pemeriksaan_laborat']['nilainormal']}} </td>
                                <td> {{ $values[$n]['pemeriksaan_laborat']['satuan']}} </td>
                                <td> {{ $values[$n]['metode'] }} </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                <?php $i++;
                } ?>

            </tbody>
        </table>


        <br>
        <p>Saran : </p>
        <p>interpretasi : </p>
        <br>
        <div class="row justify-between">
            <div style="padding-left:10%" class="column">
                <div>&nbsp </div>
                <div>Pemeriksa&nbsp;</div>
                <div style="height:60px;"></div>
                <div>(..................................)&nbsp;</div>
            </div>
            <div style="padding-right:10%" class="column">
                <div>Probolinggo, <?php
                                    $timestamp = time();
                                    $tgl = date('d F Y', $timestamp);
                                    echo $tgl;
                                    ?>&nbsp;</div>
                <div>Penanggung Jawab&nbsp;</div>
                <div style="height:60px;"></div>
                <div>(..................................)&nbsp;</div>
            </div>
        </div>
        <br>
        <!-- Scan disini untuk verifikasi :<br> -->
    </div>
</body>


</html>

<script language="javascript">
    window.print();
    window.onafterprint = function() {
        window.close();
    }
    setTimeout(function() {
        window.close();
    }, 1000);
</script>