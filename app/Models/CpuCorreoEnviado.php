<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuCorreoEnviado extends Model
{
    use HasFactory;
    protected $table = 'cpu_correos_enviados';
    protected $fillable = ['destinatarios', 'con_copia', 'asunto', 'cuerpo', 'id_paciente', 'id_funcionario_derivado', 'id_funcionario_atendio', 'created_at', 'updated_at'];

    public function paciente()
    {
        return $this->belongsTo(CpuPersona::class, 'id_paciente');
    }

    public function funcionarioDerivado()
    {
        return $this->belongsTo(User::class, 'id_funcionario_derivado');
    }

    public function funcionarioAtendio()
    {
        return $this->belongsTo(User::class, 'id_funcionario_atendio');
    }
}
