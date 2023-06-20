<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\Apuesta;
use App\Repos\DotaRepo;
use App\Models\Usuario;

class ProcesarPartidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:process';

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
        $config = DB::table('0_config')->where('id', 1)->first();

        if($config->valor == 1){
            Log::info("Ya esta en proceso las apuestas...");
            return;
        }

        // Se obtiene los match_ids en estado pendiente
        $apuestas = Apuesta::where('estado', 0)->whereNull('match_id')->get();

        Log::info("Nro Partidas a procesar: " . count($apuestas));

        if(count($apuestas) < 1){
            Log::info("No hay partidas que procesar...");
            return;
        }

        DB::table('0_config')->where('id', 1)->update(['valor'=>1]);

        $maxima_espera = DB::table('0_config')->where('id', 3)->value('valor');
        $apuesta_intervalo = DB::table('0_config')->where('id', 8)->value('valor');

        foreach($apuestas as $apuesta){

            try {

                $usuario = $apuesta->usuario;

                Log::info("USUARIO ID: " . $usuario->id);
                Log::info("timestamp apuesta: " . $apuesta->created_at);

                if(time() - strtotime($apuesta->created_at) > $maxima_espera){
                    $apuesta->estado = '2';
                    $apuesta->fecha_finalizado = time();
                    $apuesta->save();
                    continue;
                }

                $dotaRepo = new DotaRepo($usuario->steamid);
                $matches = $dotaRepo->getRecentMatches();

                if(isset($matches->error)){
                    Log::error("Hubo un error al obtener la info del API de Dota: " . $matches->error);
                    continue;
                }

                $filtered_matches = array_filter($matches, function($item) use ($apuesta){
                    //$diff = $item->start_time - strtotime($apuesta->created_at);
                    //return $diff > 0 && $diff < $apuesta_intervalo && $item->game_mode == 22 && $item->lobby_type == 7 && $item->party_size == 1;
                    return $item['start_time'] >= strtotime($apuesta->created_at);
                });

                // Si no encuentra partida, el fronted realizara una nueva busqueda
                if(count($filtered_matches) < 1){
                    Log::info("No se ha encontrado una partida para colocar en la apuesta");
                    continue;
                } else {
                    Log::info(json_encode($filtered_matches));
                    $exists = $this->findMatch($apuesta->usuario_id, $filtered_matches[0]['match_id']);

                    if($exists != null){
                        Log::info("La partida nro " . $filtered_matches[0]['match_id'] . " ya fue colocado en otra apuesta");
                        continue;
                    }
                }
                $user_id = $apuesta->usuario_id;

                $diff = $filtered_matches[0]['start_time'] - strtotime($apuesta->created_at);
                $user = Usuario::where('id', $user_id)->first();
                $key = $this->findHeroId($user->steamid, $filtered_matches[0]['players']);


                // $open_dota = $dotaRepo->getMatchesFromOpenDota();
                // Log::info("INFO OPEN DOTAAAAAAAAAA : " . json_encode($open_dota[0]->party_size));
                // $party_size = $open_dota[0]->party_size;


                if($diff > 0 && $diff < $apuesta_intervalo && $filtered_matches[0]['lobby_type'] == 7){
                    Log::info("Se encontro una partida para la apuesta #" . $apuesta->id);
                    $apuesta->match_id = $filtered_matches[0]['match_id'];
                    $apuesta->match_start_time = $filtered_matches[0]['start_time'];
                    $apuesta->match_hero_id =  $key;
                    $apuesta->fecha_proceso = time();
                    $apuesta->save();
                }
                else {
                    Log::info("Error: la diferencia de minutos es mayor a la tolerada");
                    $apuesta->match_id = $filtered_matches[0]['match_id'];
                    $apuesta->match_start_time = $filtered_matches[0]['start_time'];
                    $apuesta->match_hero_id =  $key;
                    $apuesta->estado = '3';
                    $apuesta->fecha_finalizado = time();
                    $apuesta->save();
                }
            } catch (\Exception $e){
                DB::table('0_config')->where('id', 1)->update(['valor'=>0]);
                Log::error($e);
            }
        }
        DB::table('0_config')->where('id', 1)->update(['valor'=>0]);
    }

    private function findMatch($usuario_id, $match_id){
        return Apuesta::where('usuario_id', $usuario_id)->where('match_id', $match_id)->first();
    }

    private function findHeroId($param, $array){
        $key = array_search($param , array_column($array, 'account_id'));
        if ($key === false) {
            return 99;
        } else {
            return $array[$key]['hero_id'];
        }
    }
}
