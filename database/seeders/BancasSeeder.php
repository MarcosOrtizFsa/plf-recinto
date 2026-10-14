<?php

namespace Database\Seeders;

use App\Models\Banca;
use Illuminate\Database\Seeder;

class BancasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (range(1, 30) as $numero) {
            Banca::query()->updateOrCreate(['numero' => $numero], ['nombre' => "Banca {$numero}", 'activa' => true]);
        }
    }
}
