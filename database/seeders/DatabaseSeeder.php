<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\Berita;
use App\Models\BeritaView;
use App\Models\Category;
use App\Models\Moto;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        // User::create([
        //     'username' => 'sa',
        //     'nama' => 'Programer',
        //     'email' => 'sa@app.com',
        //     'password' => bcrypt('sa12345'),
        // ]);
        // User::create([
        //     'username' => 'wan',
        //     'nama' => 'Programer',
        //     'email' => 'wan@app.com',
        //     'password' => bcrypt('wan12345'),
        // ]);
        User::create([
            'username' => 'coba',
            'nama' => 'User Coba',
            'email' => 'coba@app.com',
            'pegawai_id' => '4',
            'password' => bcrypt('coba1234'),
        ]);
    }
}
