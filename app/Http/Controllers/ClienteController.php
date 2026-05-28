<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        return Cliente::latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'apellido' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'max:40'],
            'correo' => ['required', 'email', 'max:160', 'unique:clientes,correo'],
            'fecha_nacimiento' => ['required', 'date'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'mayor_edad_confirmado' => ['accepted'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return response()->json(Cliente::create($data), 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $cliente = Cliente::where('correo', $data['correo'])
            ->where('activo', true)
            ->first();

        if (! $cliente || ! $cliente->password || ! Hash::check($data['password'], $cliente->password)) {
            throw ValidationException::withMessages([
                'correo' => ['Las credenciales no son correctas.'],
            ]);
        }

        $cliente->update(['ultimo_acceso_at' => now()]);

        return response()->json([
            'cliente' => $cliente->fresh(),
        ]);
    }

    public function show(Cliente $cliente)
    {
        return $cliente->load('reservas');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'apellido' => ['sometimes', 'required', 'string', 'max:120'],
            'telefono' => ['sometimes', 'required', 'string', 'max:40'],
            'correo' => ['sometimes', 'required', 'email', 'max:160', Rule::unique('clientes', 'correo')->ignore($cliente)],
            'fecha_nacimiento' => ['sometimes', 'required', 'date'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $cliente->update($data);

        return $cliente;
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->update(['activo' => false]);

        return response()->noContent();
    }
}
