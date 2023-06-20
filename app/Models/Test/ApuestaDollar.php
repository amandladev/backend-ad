<?php

namespace App\Models\Test;

use Illuminate\Database\Eloquent\Model;

class ApuestaDollar extends Model
{
    protected $table = "apuesta_dollar";
    protected $fillable = [
        'usuario_id','estado','match_id',
        'monto', 'multiplicador', 'match_start_time','match_hero_id',
        'fecha_proceso','fecha_finalizado',
        'isp', 'ip_address', 'pc_name'];

    public function usuario(){
        return $this->belongsTo(\App\Models\Usuario::class, 'usuario_id', 'id');
    }
}
