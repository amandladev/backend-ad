<?php
/**
 * @author Kevin Baylón <kbaylonh@outlook.com>
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Usuario;
use App\Models\Test\ApuestaDollar;
use App\Repos\DotaRepo;
use App\Repos\BalanceRepo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckMatchesDollar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches_dollar:check';

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
        // Se obtiene los match_ids en estado pendiente
        $apuestas = ApuestaDollar::where('estado', 0)->groupBy('match_id')->select('match_id')->get();

        foreach($apuestas as $apuesta){

            try {
                // Buscamos la partida con el api de dota
                $match = (new DotaRepo)->findMatch($apuesta->match_id);
                if($match !== null){
                    // Obtenemos los jugadores de la partida
                    $players = $match['players'];
                    $radiant_win = $match['radiant_win'];
                    // $user_id = $apuesta->usuario_id;
                    // Filtramos a los que ganaron
                if ($radiant_win == true) {
                    $winners = array_filter($players, function($item){
                        return $item['team_number'] == 0;
                    });

                        // Recorremos a los ganadores
                    foreach($winners as $winner){
                        // Obtenemos el usuario mediante el steamid
                        $user = Usuario::where('steamid', $winner['account_id'])->first();

                        // Validamos si el ganador esta en nuestra BD
                        if($user !== null){

                            // buscamos su partida (apuesta)
                            $user_partida = $user->apuestas_dollar()->where('match_id', $apuesta->match_id)->first();

                            if($user_partida !== null){
                                // Aumentar saldo
                                $balanceRepo = new BalanceRepo();
                                $balanceRepo->setUsuario($user);
                                $balanceRepo->increase(($user_partida->monto * $user_partida->multiplicador), 'balance_dollar');

                                // marcado como ganado
                                // $user_partida->match_hero_id = $winner['hero_id'];
                                $user_partida->fecha_finalizado = time();
                                $user_partida->estado = '1';
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
                            $user_partida = $usuario->apuestas_dollar()->where('match_id', $apuesta->match_id)->first();

                            if($user_partida !== null){
                                // Aumentar saldo
                                $monto_ganado = $user_partida->monto * $user_partida->multiplicador;
                                $balanceRepo = new BalanceRepo();
                                $balanceRepo->setUsuario($usuario);
                                $balanceRepo->increase($monto_ganado, 'balance_dollar');


                                // marcado como ganado
                                // $user_partida->match_hero_id = $loser['hero_id'];
                                $user_partida->fecha_finalizado = time();
                                $user_partida->estado = '1';
                                $user_partida->save();
                            }
                        }
                    }
                }

                    // Marcar las apuestas de los otros participantes como perdidas (estado = 2)
                    ApuestaDollar::where('match_id', $apuesta->match_id)->where('estado', '0')->update(['estado'=>'2','fecha_finalizado'=>time()]);
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        // DB::table('0_config')->where('id', 10)->update(['valor'=>0]);
    }

}
