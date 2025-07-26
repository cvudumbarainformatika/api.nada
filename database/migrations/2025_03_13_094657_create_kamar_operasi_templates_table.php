<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKamarOperasiTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('farmasi')->create('kamar_operasi_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('sistembayar');
            $table->unsignedBigInteger('pegawai_id');
            $table->enum('user', ['public', 'private'])->default('public');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('farmasi')->dropIfExists('kamar_operasi_templates');
    }
}
