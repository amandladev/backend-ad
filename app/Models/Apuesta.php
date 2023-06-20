<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apuesta extends Model
{
    protected $table = "apuesta";
    protected $fillable = [
        'usuario_id','estado','match_id',
        'monto', 'multiplicador', 'match_start_time','match_hero_id',
        'fecha_proceso','fecha_finalizado',
        'isp', 'ip_address', 'pc_name', 'isShared', 'kills', 'deaths', 'assists', 'tipo', 'party_size'];

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }
}
