<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Suscripcion extends Model
{
    protected $table = 'suscripcion';

    protected $primaryKey = 'id_suscripcion';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'metodo_pago',
        'monto_pagado',
        'id_plan',
        'id_motel',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'monto_pagado' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan');
    }

    public function motel()
    {
        return $this->belongsTo(Motel::class, 'id_motel');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActivas(Builder $query)
    {
        return $query->where('estado', 'ACTIVA');
    }

    public function scopeTrial(Builder $query)
    {
        return $query->where('estado', 'TRIAL');
    }

    public function scopeVencidas(Builder $query)
    {
        return $query->where('estado', 'VENCIDA');
    }
}