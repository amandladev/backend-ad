<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\Apuesta;
use App\Repos\DotaRepo;
use App\Models\Usuario;

class CheckTestPartySize extends Command
{
     /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checktestparty:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca las partidas (apuestas) y los procesa';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::table('apuesta_prueba')
        ->where('party_size', null)
        ->where('estado', '=', 0)
        ->whereNotNull('match_id')
        ->groupBy('match_id')
        ->select(DB::raw('match_id, COUNT(*) AS count'))
        ->get()
        ->each(function ($apuesta_prueba) {
            DB::table('apuesta_prueba')
                ->where('match_id', $apuesta_prueba->match_id)
                ->where('party_size', null)
                ->where('estado', '=', 0)
                ->update(['party_size' => $apuesta_prueba->count]);
        });
    }
}
