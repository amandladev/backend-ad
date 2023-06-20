<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUser;
use Illuminate\Http\Request;
use App\Repos\UsuarioRepo;


class UsuarioController extends Controller
{
    //
    public function getSaldo(){
        $usuario = auth()->user();
        return response()->json(['saldo'=>number_format($usuario->balance, 2), 'saldo_prueba'=>number_format($usuario->balance_prueba, 2), 'saldo_switch'=>$usuario->balance_switch]);
    }

    public function switchSaldo(Request $req){
        $usuario = auth()->user();
        $switch = $req->input('switch');
        $test_mode = $switch;
        $balance_switch = $switch == 0 ? 'balance' : ($switch == 1 ? 'balance_prueba' : ($switch == 2 ? 'balance_dollar' : 'balance_test_dollar'));
        $usuario->update([
            'balance_switch' => $balance_switch,
            'test_mode' => $test_mode
        ]);
        return response()->json(['saldo_switch'=>$usuario->balance_switch]);
    }

    public function getProfile(){
        return response()->json(auth()->user());
    }

    public function searchAll(){
        return (new UsuarioRepo(auth()->user()))->searchAll();
    }

    public function update(UpdateUser $req){
        try {
            (new \App\Actions\UpdateProfile)->execute($req);
            return response()->json(['success'=>true]);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()]);
        }
    }

}
