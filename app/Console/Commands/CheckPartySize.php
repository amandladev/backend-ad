<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\Apuesta;
use App\Models\Test\ApuestaTest;
use App\Repos\DotaRepo;
use App\Models\Usuario;

class CheckPartySize extends Command
{
     /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkparty:process';

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
        
            
        
        DB::table('apuesta')
        ->where('party_size', null)
        ->where('estado', '=', 0)
        ->whereNotNull('match_id')
        ->groupBy('match_id')
        ->select(DB::raw('match_id, COUNT(*) AS count'))
        ->get()
        ->each(function ($apuesta) {
            DB::table('apuesta')
                ->where('match_id', $apuesta->match_id)
                ->where('party_size', null)
                ->where('estado', '=', 0)
                ->update(['party_size' => $apuesta->count]);
        });
    /*SECOND PART */
    Log::info("starting second part");
                DB::table('apuesta_prueba')
                ->where('party_size', null)
                ->where('estado', '=', 0)
                ->whereNotNull('match_id')
                ->groupBy('match_id')
                ->select(DB::raw('match_id, COUNT(*) AS count'))
                ->get()
                ->each(function ($apuesta) {
                    DB::table('apuesta_prueba')
                        ->where('match_id', $apuesta->match_id)
                        ->where('party_size', null)
                        ->where('estado', '=', 0)
                        ->update(['party_size' => $apuesta->count]);
                });
                Log::info("finishing second part");
    }
}
