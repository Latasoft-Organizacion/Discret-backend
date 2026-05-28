<?php

namespace App\Http\Controllers;

use App\Models\EnvioProgramado;
use App\Models\Reserva;
use App\Models\Valoracion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    public function index(Request $request)
    {
        return Reserva::query()
            ->with(['cliente', 'habitacion.tipoHabitacion', 'valoracion'])
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = $request->buscar;

                $query->where(function ($subQuery) use ($buscar) {
                    $subQuery
                        ->where('codigo_reserva', 'like', "%{$buscar}%")
                        ->orWhere('nombre_cliente', 'like', "%{$buscar}%")
                        ->orWhere('telefono_cliente', 'like', "%{$buscar}%")
                        ->orWhere('correo_cliente', 'like', "%{$buscar}%");
                });
            })
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'habitacion_id' => ['required', 'exists:habitaciones,id'],
            'nombre_cliente' => ['nullable', 'string', 'max:160'],
            'telefono_cliente' => ['nullable', 'string', 'max:40'],
            'correo_cliente' => ['nullable', 'email', 'max:160'],
            'cantidad_personas' => ['required', 'integer', 'min:1', 'max:6'],
            'fecha_entrada' => ['required', 'date'],
            'fecha_salida' => ['required', 'date', 'after:fecha_entrada'],
            'estado' => ['sometimes', Rule::in(['pendiente', 'confirmada', 'ocupada', 'finalizada', 'cancelada'])],
            'tipo_pago' => ['nullable', Rule::in(['efectivo', 'transferencia', 'tarjeta', 'online'])],
            'comentario' => ['nullable', 'string'],
        ]);

        $data['codigo_reserva'] = $this->generarCodigoReserva();
        $data['qr_token'] = Str::uuid()->toString();

        $reserva = Reserva::create($data);

        return response()->json($reserva->load(['cliente', 'habitacion.tipoHabitacion']), 201);
    }

    public function show(Reserva $reserva)
    {
        return $reserva->load(['cliente', 'habitacion.tipoHabitacion', 'valoracion']);
    }

    public function update(Request $request, Reserva $reserva)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'habitacion_id' => ['sometimes', 'required', 'exists:habitaciones,id'],
            'nombre_cliente' => ['nullable', 'string', 'max:160'],
            'telefono_cliente' => ['nullable', 'string', 'max:40'],
            'correo_cliente' => ['nullable', 'email', 'max:160'],
            'cantidad_personas' => ['sometimes', 'required', 'integer', 'min:1', 'max:6'],
            'fecha_entrada' => ['sometimes', 'required', 'date'],
            'fecha_salida' => ['sometimes', 'required', 'date'],
            'estado' => ['sometimes', Rule::in(['pendiente', 'confirmada', 'ocupada', 'finalizada', 'cancelada'])],
            'tipo_pago' => ['nullable', Rule::in(['efectivo', 'transferencia', 'tarjeta', 'online'])],
            'comentario' => ['nullable', 'string'],
        ]);

        $reserva->update($data);

        if (($data['estado'] ?? null) === 'finalizada') {
            $this->programarValoracion($reserva->fresh());
        }

        return $reserva->load(['cliente', 'habitacion.tipoHabitacion', 'valoracion']);
    }

    public function destroy(Reserva $reserva)
    {
        $reserva->update(['estado' => 'cancelada']);

        return response()->noContent();
    }

    private function generarCodigoReserva(): string
    {
        $nextId = (Reserva::max('id') ?? 0) + 1;

        return 'DIS-'.now()->format('Y').'-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }

    private function programarValoracion(Reserva $reserva): void
    {
        $valoracion = Valoracion::firstOrCreate(
            ['reserva_id' => $reserva->id],
            [
                'cliente_id' => $reserva->cliente_id,
                'token' => Str::uuid()->toString(),
            ],
        );

        EnvioProgramado::firstOrCreate(
            [
                'reserva_id' => $reserva->id,
                'tipo' => 'valoracion_post_salida',
            ],
            [
                'cliente_id' => $reserva->cliente_id,
                'canal' => 'correo',
                'destinatario' => $reserva->correo_cliente ?? $reserva->cliente?->correo ?? 'pendiente@discret.cl',
                'asunto' => 'Cuéntanos cómo fue tu experiencia en DISCRET',
                'mensaje' => url("/valoracion/{$valoracion->token}"),
                'programado_para' => $reserva->fecha_salida->copy()->addMinutes(30),
                'estado' => 'pendiente',
            ],
        );
    }
}
