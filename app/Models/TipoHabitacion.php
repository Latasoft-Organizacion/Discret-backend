<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoHabitacion extends Model
{
    protected $table = 'tipo_habitaciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_base',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_base' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function habitaciones(): HasMany
    {
        return $this->hasMany(Habitacion::class);
    }
}
