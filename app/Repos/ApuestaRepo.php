<?php namespace App\Repos;

use App\Models\Usuario;
use App\Models\Apuesta;
use App\Models\Test\ApuestaTest;
use App\Models\Test\ApuestaTestDollar;
use App\Models\Test\ApuestaDollar;

class ApuestaRepo {

    private $usuario;

    public function __construct(Usuario $usuario)
    {
        $this->usuario = $usuario;
    }

    public function create($monto, $multiplcador = 1.3, $tipo, $params = null){
        $apuesta = $this->usuario->test_mode == 0 ? new Apuesta() : ( $this->usuario->test_mode == 1  ? new ApuestaTest() :  ($this->usuario->test_mode == 2 ? new ApuestaDollar() : new ApuestaTestDollar()));
        // if ($this->usuario->test_mode == 0) {
        //     $apuesta = new Apuesta();
        // } else if ($this->usuario->test_mode == 1) {
        //     $apuesta = new ApuestaTest();
        // } else {
        //     $apuesta = new ApuestaDollar();
        // }

        $apuesta->usuario_id = $this->usuario->id;
        $apuesta->estado = '0'; // pendiente
        $apuesta->tipo = $tipo;
        $apuesta->monto = $monto;
        $apuesta->multiplicador = $multiplcador;

        if(isset($params['isp']))
        $apuesta->isp = $params['isp'];

        if(isset($params['pc_name']))
        $apuesta->pc_name = $params['pc_name'];

        if(isset($params['ip_address']))
        $apuesta->ip_address = $params['ip_address'];

        if(isset($params['ganancia']))
        $apuesta->ganancia = $params['ganancia'];

        $apuesta->save();

        return $apuesta;
    }

    public function find($partida_id){
        return $this->usuario->apuestas()->find($partida_id);
    }

    public function search(){
        $partidaModel = $this->usuario->test_mode == 1 ? ApuestaTest::query() : Apuesta::query();
        return $partidaModel->where('usuario_id', $this->usuario->id)->orderBy('created_at', 'DESC')->get();
    }



    public function getEmptyApuesta(){
        $result = $this->usuario->test_mode == 1 ? ApuestaTest::query() : Apuesta::query();
        return $result->where('usuario_id', $this->usuario->id)->where('estado','0')->first();
    }
}
