<?php

namespace App\Http\Controllers;

use App\Models\EnvioProgramado;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\Valoracion;
use App\Support\CurrentMotel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    public function index(Request $request)
    {
        $idMotel = CurrentMotel::id($request);

        return Reserva::query()
            ->with(['cliente', 'habitacion.tipoHabitacion', 'motel', 'valoracion'])
            ->where('id_motel', $idMotel)
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
        $idMotel = CurrentMotel::id($request);

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

        $habitacion = Habitacion::where('id', $data['habitacion_id'])
            ->where('id_motel', $idMotel)
            ->firstOrFail();

        $data['id_motel'] = $habitacion->id_motel;
        $data['codigo_reserva'] = $this->generarCodigoReserva();
        $data['qr_token'] = Str::uuid()->toString();

        $reserva = Reserva::create($data);

        return response()->json($reserva->load(['cliente', 'habitacion.tipoHabitacion', 'motel']), 201);
    }

    public function show(Reserva $reserva)
    {
        return $reserva->load(['cliente', 'habitacion.tipoHabitacion', 'motel', 'valoracion']);
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

        if (isset($data['habitacion_id'])) {
            $habitacion = Habitacion::where('id', $data['habitacion_id'])
                ->where('id_motel', $reserva->id_motel)
                ->firstOrFail();

            $data['id_motel'] = $habitacion->id_motel;
        }

        $reserva->update($data);

        if (($data['estado'] ?? null) === 'finalizada') {
            $this->programarValoracion($reserva->fresh());
        }

        return $reserva->load(['cliente', 'habitacion.tipoHabitacion', 'motel', 'valoracion']);
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
                'id_motel' => $reserva->id_motel,
                'token' => Str::uuid()->toString(),
            ],
        );
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173/discret'), '/');

        EnvioProgramado::firstOrCreate(
            [
                'reserva_id' => $reserva->id,
                'tipo' => 'valoracion_post_salida',
            ],
            [
                'cliente_id' => $reserva->cliente_id,
                'id_motel' => $reserva->id_motel,
                'canal' => 'correo',
                'destinatario' => $reserva->correo_cliente ?? $reserva->cliente?->correo ?? 'pendiente@discret.cl',
                'asunto' => 'Cuéntanos cómo fue tu experiencia en DISCRET',
                'mensaje' => "{$frontendUrl}/valoracion/{$valoracion->token}",
                'programado_para' => $reserva->fecha_salida->copy()->addMinutes(30),
                'estado' => 'pendiente',
            ],
        );
    }
}
