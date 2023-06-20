<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\Usuario;
use App\Models\Test\ApuestaTest;
use App\Repos\DotaRepo;


class ProcesarApuestasTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apuestas_test:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca las apuestas de prueba y los procesa';

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
        $config = DB::table('0_config')->where('id', 4)->first();

        if($config->valor == 1){
            Log::info("Ya esta en proceso las apuestas de practica...");
            return;
        }

        // Se obtiene los match_ids en estado pendiente
        $apuestas = ApuestaTest::where('estado', 0)->whereNull('match_id')->get();

        Log::info("Nro Partidas de practica a procesar: " . count($apuestas));

        if(count($apuestas) < 1){
            Log::info("No hay partidas de practica que procesar...");
            return;
        }

        DB::table('0_config')->where('id', 4)->update(['valor'=>1]);

        $maxima_espera = DB::table('0_config')->where('id', 3)->value('valor');
        $apuesta_intervalo = DB::table('0_config')->where('id', 8)->value('valor');

        foreach($apuestas as $apuesta){

            try {

                $usuario = $apuesta->usuario;

                Log::info("ID USUARIO: " . $usuario->id);
                Log::info("timestamp apuesta de practica: " . $apuesta->created_at);

                if(time() - strtotime($apuesta->created_at) > $maxima_espera){
                    $apuesta->fecha_finalizado = time();
                    $apuesta->estado = '2';
                    $apuesta->save();
                    continue;
                }

                $dotaRepo = new DotaRepo($usuario->steamid);
                $matches = $dotaRepo->getRecentMatches();

                if(isset($matches->error)){
                    Log::info("Hubo un error al obtener la info del API de Dota: " . $matches['error']);
                    continue;
                }

                $filtered_matches = array_filter($matches, function($item) use ($apuesta){
                    //$diff = $item->start_time - strtotime($apuesta->created_at);
                    //return $diff > 0 && $diff < $apuesta_intervalo && $item->game_mode == 22 && $item->lobby_type == 7 && $item->party_size == 1;
                    return $item['start_time'] >= strtotime($apuesta->created_at);
                });

                // Si no encuentra partida, el fronted realizara una nueva busqueda
                if(count($filtered_matches) < 1){
                    Log::info("No se ha encontrado una partida para colocar en la apuesta de practica");
                    continue;
                } else {
                    // Log::info(json_encode($filtered_matches));
                    $exists = $this->findMatch($apuesta->usuario_id, $filtered_matches[0]['match_id']);
                    if($exists != null){
                        Log::info("La partida nro " . $filtered_matches[0]['match_id'] . " ya fue colocado en otra apuesta de practica");
                        continue;
                    }
                }
                // $match = $dotaRepo->findMatch($filtered_matches[0]['match_id']);

                // Log::info(json_encode($match));

                $user_id = $apuesta->usuario_id;

                $diff = $filtered_matches[0]['start_time'] - strtotime($apuesta->created_at);
                $user = Usuario::where('id', $user_id)->first();
                $key = $this->findHeroId($user->steamid, $filtered_matches[0]['players']);


                if($diff > 0 && $diff < $apuesta_intervalo && $filtered_matches[0]['lobby_type'] == 7) {
                    Log::info("Se encontro una partida para la apuesta de PRUEBA #" . $apuesta->id);
                    $apuesta->match_id = $filtered_matches[0]['match_id'];
                    $apuesta->match_start_time = $filtered_matches[0]['start_time'];
                    $apuesta->match_hero_id = $key;
                    $apuesta->fecha_proceso = time();
                    $apuesta->save();
                }
                else {
                    Log::info("Error: la diferencia de minutos es mayor a la tolerada");
                    $apuesta->match_id = $filtered_matches[0]['match_id'];
                    $apuesta->match_start_time = $filtered_matches[0]['start_time'];
                    $apuesta->match_hero_id = $key;
                    $apuesta->estado = '3';
                    $apuesta->fecha_finalizado = time();
                    $apuesta->save();
                }
            } catch (\Exception $e){
                DB::table('0_config')->where('id', 4)->update(['valor'=>0]);
                Log::error($e);
            }
        }
        DB::table('0_config')->where('id', 4)->update(['valor'=>0]);
    }

    private function findMatch($usuario_id, $match_id){
        return ApuestaTest::where('usuario_id', $usuario_id)->where('match_id', $match_id)->first();
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
