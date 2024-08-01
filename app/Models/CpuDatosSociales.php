<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosSociales extends Model
{
    
        use HasFactory;
    
        protected $table = 'cpu_datos_sociales';
    
        protected $fillable = [
            'id_persona',
            'persona',
            'diagnostico',
            'parentesco',
            'problema_salud',
            'markers',
            'image_path'
        ];
    
        protected $casts = [
            'persona' => 'array',
            'markers' => 'array',
            'problema_salud' => 'boolean'
        ];
    
        public function persona()
        {
            return $this->belongsTo(Persona::class, 'id_persona');
        }
    }
