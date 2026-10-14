<?php

namespace App\Console\Commands;

use App\Services\Recinto\GestorRecinto;
use Illuminate\Console\Command;

class CerrarVotacionesVencidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'votaciones:cerrar-vencidas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra y publica las votaciones cuyo tiempo global finalizó';

    /**
     * Execute the console command.
     */
    public function handle(GestorRecinto $gestor): int
    {
        $cantidad = $gestor->cerrarVencidas();
        $this->info("Votaciones cerradas: {$cantidad}");

        return self::SUCCESS;
    }
}
