<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Datos de demostración (todo ficticio). */
    public function run(): void
    {
        $this->call([
            TipoTramiteSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
