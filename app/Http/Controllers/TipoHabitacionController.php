<?php

namespace App\Http\Controllers;

use App\Models\TipoHabitacion;
use Illuminate\Http\Request;

class TipoHabitacionController extends Controller
{
    public function index()
    {
        return TipoHabitacion::where('activo', true)->orderBy('nombre')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'precio_base' => ['required', 'integer', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        return response()->json(TipoHabitacion::create($data), 201);
    }

    public function show(TipoHabitacion $tipoHabitacion)
    {
        return $tipoHabitacion->load('habitaciones');
    }

    public function update(Request $request, TipoHabitacion $tipoHabitacion)
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'precio_base' => ['sometimes', 'required', 'integer', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $tipoHabitacion->update($data);

        return $tipoHabitacion;
    }

    public function destroy(TipoHabitacion $tipoHabitacion)
    {
        $tipoHabitacion->update(['activo' => false]);

        return response()->noContent();
    }
}
