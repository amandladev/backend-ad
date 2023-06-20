<?php
/**
 * @author Kevin Baylón <kbaylonh@outlook.com>
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Usuario;
use App\Models\Test\ApuestaTest;
use App\Repos\DotaRepo;
use App\Repos\BalanceRepo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckMatchesTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches_test:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca las partidas (apuestas) en estado 0 (pendientes) y realiza los pagos a los ganadores';

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
        $config = DB::table('0_config')->where('id', 5)->first();

        if($config->valor == 1){
            Log::info("Ya estan finalizando las apuestas...");
            return;
        }

        DB::table('0_config')->where('id', 5)->update(['valor'=>1]);

        // Se obtiene los match_ids en estado pendiente
        // $apuestas = ApuestaTest::where('estado', 0)->groupBy('match_id')->select('match_id', 'usuario_id')->get();
        $apuestas = ApuestaTest::where('estado', 0)->whereNotNull('party_size')->groupBy('match_id')->select('match_id', 'usuario_id', 'party_size')->get();
        foreach($apuestas as $apuesta){
            try {
                // Buscamos la partida con el api de dota
                $match = (new DotaRepo)->findMatch($apuesta->match_id);
                if($match !== null){

                    if(isset($match['players'])){

                    $players = $match['players'];
                    $radiant_win = $match['radiant_win'];
                    // $user_id = $apuesta->usuario_id;

                    // Filtramos a los que ganaron
                    if ($radiant_win == true && $players !== null) {
                    $winners = array_filter($players, function($item){
                        return $item['team_number'] == 0;
                    });

                    // Recorremos a los ganadores
                    foreach($winners as $winner){
                        // Obtenemos el usuario mediante el steamid
                        $usuario = Usuario::where('steamid', $winner['account_id'])->first();
                        // Validamos si el ganador esta en nuestra BD
                        if($usuario !== null){
                            // buscamos su partida (apuesta)
                            $user_partida = $usuario->apuestas_test()->where('match_id', $apuesta->match_id)->first();

                            if($user_partida !== null){
                                $balanceRepo = new BalanceRepo();
                                $balanceRepo->setUsuario($usuario);
                                $bet_state = false;
                                $monto_ganado = $user_partida->monto * $user_partida->multiplicador;

                                // Aumentar saldo
                                switch ($user_partida->tipo) {
                                    case '1':
                                        // Increase balance for type 1
                                        if ($user_partida->party_size == 1) {
                                            $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                        } else {
                                            $user_partida->multiplicador = 1.2;
                                            $monto_ganado = $user_partida->monto * 1.2;
                                            $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                        }
                                        $bet_state = true;
                                        break;
                                    case '2':
                                        // Increase balance if winner's kills are greater than or equal to 29 for type 2
                                        if ($winner['kills'] >= 29) {
                                            if ($user_partida->party_size == 1) {
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            } else {
                                                $user_partida->multiplicador = 1.3;
                                                $monto_ganado = $user_partida->monto * 1.3;
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            }
                                            $bet_state = true;
                                        }
                                        break;
                                    case '3':
                                        // Increase balance if winner's assists are greater than or equal to 32 for type 3
                                        if ($winner['assists'] >= 32) {
                                            if ($user_partida->party_size == 1) {
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            } else {
                                                $user_partida->multiplicador = 1.3;
                                                $monto_ganado = $user_partida->monto * 1.3;
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            }
                                            $bet_state = true;
                                        }
                                        break;
                                    case '4':
                                        // Increase balance if winner's kills are greater than or equal to 18 and deaths are 0 for type 4
                                        if ($winner['kills'] >= 18 && $winner['deaths'] == 0) {
                                            if ($user_partida->party_size == 1) {
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            } else {
                                                $user_partida->multiplicador = 1.6;
                                                $monto_ganado = $user_partida->monto * 1.6;
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            }
                                            $bet_state = true;
                                        }
                                        break;
                                    case '5':
                                        // Increase balance if winner's deaths are 0 for type 5
                                        if ($winner['deaths'] === 0) {
                                            if ($user_partida->party_size == 1) {
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            } else {
                                                $user_partida->multiplicador = 1.4;
                                                $monto_ganado = $user_partida->monto * 1.4;
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            }
                                            $bet_state = true;
                                        }
                                        break;
                                    case '6':
                                        // Increase balance if user's hero id is 5, 87, 64, or 33 for type 6
                                        if ($user_partida->match_hero_id == 5 || $user_partida->match_hero_id == 87 || $user_partida->match_hero_id == 33 || $user_partida->match_hero_id == 91 || $user_partida->match_hero_id == 111) {
                                            if ($user_partida->party_size == 1) {
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            } else {
                                                $user_partida->multiplicador = 1.25;
                                                $monto_ganado = $user_partida->monto * 1.25;
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            }
                                            $bet_state = true;
                                        }
                                        break;
                                    case '7':
                                        // Increase balance if user's hero id is 14, 71, 87, or 2 for type 7
                                        if ($user_partida->match_hero_id == 14 || $user_partida->match_hero_id == 71 || $user_partida->match_hero_id == 19 || $user_partida->match_hero_id == 16 || $user_partida->match_hero_id == 83) {
                                            if ($user_partida->party_size == 1) {
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            } else {
                                                $user_partida->multiplicador = 1.25;
                                                $monto_ganado = $user_partida->monto * 1.25;
                                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                            }
                                            $bet_state = true;
                                        }
                                        break;
                                    default:
                                        // Update Apuesta state to 2 and return if none of the above conditions match
                                       break;
                                    }
                                // marcado como ganado
                                $user_partida->kills = $winner['kills'];
                                $user_partida->deaths = $winner['deaths'];
                                $user_partida->assists = $winner['assists'];
                                $user_partida->fecha_finalizado = time();
                                if ($bet_state == true) {
                                    Log::info("El usuario con ID, ha GANADO su partida de practica: ". $user_partida->usuario_id);
                                    $user_partida->estado = '1';
                                } else {
                                    Log::info("El usuario con ID, ha PERDIDO su partida de practica: ". $user_partida->usuario_id);
                                    $user_partida->estado = '2';
                                }
                                $user_partida->save();
                            }
                        }
                    }
                } else {
                    $losers = array_filter($players, function($item){
                        return $item['team_number'] == 1;
                    });
                    foreach($losers as $loser){
                    // Obtenemos el usuario mediante el steamid
                    $usuario = Usuario::where('steamid', $loser['account_id'])->first();
                    // Validamos si el ganador esta en nuestra BD
                    if($usuario !== null){
                        // buscamos su partida (apuesta)
                        $user_partida = $usuario->apuestas_test()->where('match_id', $apuesta->match_id)->first();

                        if($user_partida !== null){

                        $balanceRepo = new BalanceRepo();
                        $balanceRepo->setUsuario($usuario);
                        $bet_state = false;

                        switch ($user_partida->tipo) {
                            case '1':
                                // Increase balance for type 1
                                if ($user_partida->party_size > 1) {
                                    $user_partida->multiplicador = 1.2;
                                }
                                $bet_state = true;
                                break;
                            case '2':
                                // Increase balance if loser's kills are greater than or equal to 29 for type 2
                                if ($loser['kills'] >= 29) {
                                    if ($user_partida->party_size > 1) {
                                        $user_partida->multiplicador = 1.3;
                                    }
                                    $bet_state = true;
                                }
                                break;
                            case '3':
                                // Increase balance if loser's assists are greater than or equal to 32 for type 3
                                if ($loser['assists'] >= 32) {
                                    if ($user_partida->party_size > 1) {
                                        $user_partida->multiplicador = 1.3;
                                    }
                                    $bet_state = true;
                                }
                                break;
                            case '4':
                                // Increase balance if loser's kills are greater than or equal to 18 and deaths are 0 for type 4
                                if ($loser['kills'] >= 18 && $loser['deaths'] == 0) {
                                    if ($user_partida->party_size > 1) {
                                        $user_partida->multiplicador = 1.6;
                                    }
                                    $bet_state = true;
                                }
                                break;
                            case '5':
                                // Increase balance if loser's deaths are 0 for type 5
                                if ($loser['deaths'] === 0) {
                                    if ($user_partida->party_size > 1) {
                                        $user_partida->multiplicador = 1.4;
                                    }
                                    $bet_state = true;
                                }
                                break;
                            case '6':
                                // Increase balance if user's hero id is 5, 87, 64, or 33 for type 6
                                if ($user_partida->match_hero_id == 5 || $user_partida->match_hero_id == 87 || $user_partida->match_hero_id == 33 || $user_partida->match_hero_id == 91 || $user_partida->match_hero_id == 111) {
                                    if ($user_partida->party_size > 1) {
                                        $user_partida->multiplicador = 1.25;
                                    }
                                    $bet_state = true;
                                }
                                break;
                            case '7':
                                // Increase balance if user's hero id is 14, 71, 87, or 2 for type 7
                                if ($user_partida->match_hero_id == 14 || $user_partida->match_hero_id == 71 || $user_partida->match_hero_id == 19 || $user_partida->match_hero_id == 16 || $user_partida->match_hero_id == 83) {
                                    if ($user_partida->party_size > 1) {
                                        $user_partida->multiplicador = 1.25;
                                    }
                                    $bet_state = true;
                                }
                                break;
                            default:
                                // Update Apuesta state to 2 and return if none of the above conditions match
                              break;
                            }

                            $user_partida->kills = $loser['kills'];
                            $user_partida->deaths = $loser['deaths'];
                            $user_partida->assists = $loser['assists'];

                            if ($bet_state == true) {
                                Log::info("El usuario con ID, ha GANADO su partida de practica: ". $user_partida->usuario_id);
                                $monto_ganado = $user_partida->monto * $user_partida->multiplicador;
                                $balanceRepo->increase($monto_ganado, 'balance_prueba');
                                $user_partida->estado = '1';
                            } else {
                                Log::info("El usuario con ID, ha PERDIDO su partida de practica: ". $user_partida->usuario_id);
                                $user_partida->estado = '2';
                            }
                            $user_partida->fecha_finalizado = time();
                            $user_partida->save();
                        }
                    }
                }
            }
                    // Marcar las apuestas de los otros participantes como perdidas (estado = 2)
                    ApuestaTest::where('match_id', $apuesta->match_id)->where('estado', '0')->update(['estado'=>'2','fecha_finalizado'=>time()]);
                }
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        DB::table('0_config')->where('id', 5)->update(['valor'=>0]);
    }

    // private function findHeroId($param, $array){
    //     $key = array_search($param , array_column($array, 'account_id'));
    //     if ($key === false) {
    //         return 99;
    //     } else {
    //         return $array[$key]['hero_id'];
    //     }
    // }
}
