<?php namespace App\Repos;

use App\Models\Usuario;
use App\Models\Apuesta;
use App\Models\Test\ApuestaTest;
 
class ApuestaAllRepo {
    public function searchAll(){
        $partidaModel = Apuesta::query();
        return $partidaModel->where('estado', 1)->orderBy('created_at', 'DESC')->get();
    }
}