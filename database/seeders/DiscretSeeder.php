<?php

namespace Database\Seeders;

use App\Models\ConfiguracionSistema;
use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use App\Models\User;
use App\Models\Motel;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DiscretSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'correo@ejemplo.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ],
        );

        $motel = Motel::updateOrCreate(
            ['nombre' => 'Motel Discret'],
            [
                'direccion' => 'Dirección demo',
                'telefono' => '000000000',
                'email' => 'motel@demo.com',
                'ventana_limpieza' => 30,
                'activo' => 1,
                'slug' => 'motel-discret',

            ]
        );

        $planBasico = Plan::updateOrCreate(
            ['nombre' => 'BASE'],
            [
                'precio' => 49990,
                'max_habitaciones' => 10,
                'activo' => true,
            ]
        );

        $planDiscret = Plan::updateOrCreate(
            ['nombre' => 'DISCRET'],
            [
                'precio' => 59990,
                'max_habitaciones' => 25,
                'activo' => true,
            ]
        );


        $tipos = [
            ['nombre' => 'Habitación estándar', 'descripcion' => 'Habitación cómoda y privada.', 'precio_base' => 25000],
            ['nombre' => 'Habitación premium', 'descripcion' => 'Experiencia premium con mayor confort.', 'precio_base' => 35000],
            ['nombre' => 'Suite jacuzzi', 'descripcion' => 'Suite equipada con jacuzzi.', 'precio_base' => 45000],
            ['nombre' => 'Suite temática', 'descripcion' => 'Suite con ambientación especial.', 'precio_base' => 55000],
        ];

        foreach ($tipos as $tipo) {
            TipoHabitacion::updateOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }

        $tipoEstandar = TipoHabitacion::where('nombre', 'Habitación estándar')->first();
        $tipoPremium = TipoHabitacion::where('nombre', 'Habitación premium')->first();
        $tipoJacuzzi = TipoHabitacion::where('nombre', 'Suite jacuzzi')->first();
        $tipoTematica = TipoHabitacion::where('nombre', 'Suite temática')->first();

        $habitaciones = [
            ['numero' => '101', 'tipo_habitacion_id' => $tipoEstandar->id, 'nombre' => 'Habitación 101', 'descripcion' => 'Suite estándar', 'precio' => 25000, 'estado' => 'disponible', 'id_motel' => $motel->id_motel],
            ['numero' => '102', 'tipo_habitacion_id' => $tipoPremium->id, 'nombre' => 'Habitación 102', 'descripcion' => 'Suite premium', 'precio' => 35000, 'estado' => 'ocupada', 'id_motel' => $motel->id_motel],
            ['numero' => '103', 'tipo_habitacion_id' => $tipoJacuzzi->id, 'nombre' => 'Habitación 103', 'descripcion' => 'Suite jacuzzi', 'precio' => 45000, 'estado' => 'limpieza', 'id_motel' => $motel->id_motel],
            ['numero' => '104', 'tipo_habitacion_id' => $tipoTematica->id, 'nombre' => 'Habitación 104', 'descripcion' => 'Suite temática', 'precio' => 55000, 'estado' => 'ocupada', 'id_motel' => $motel->id_motel],
            ['numero' => '105', 'tipo_habitacion_id' => $tipoEstandar->id, 'nombre' => 'Habitación 105', 'descripcion' => 'Suite estándar', 'precio' => 25000, 'estado' => 'disponible', 'id_motel' => $motel->id_motel],
            ['numero' => '106', 'tipo_habitacion_id' => $tipoPremium->id, 'nombre' => 'Habitación 106', 'descripcion' => 'Suite premium', 'precio' => 35000, 'estado' => 'disponible', 'id_motel' => $motel->id_motel],
            ['numero' => '107', 'tipo_habitacion_id' => $tipoJacuzzi->id, 'nombre' => 'Habitación 107', 'descripcion' => 'Suite jacuzzi', 'precio' => 45000, 'estado' => 'ocupada', 'id_motel' => $motel->id_motel],
            ['numero' => '108', 'tipo_habitacion_id' => $tipoTematica->id, 'nombre' => 'Habitación 108', 'descripcion' => 'Suite temática', 'precio' => 55000, 'estado' => 'disponible', 'id_motel' => $motel->id_motel],
        ];

        foreach ($habitaciones as $habitacion) {
            Habitacion::updateOrCreate(['numero' => $habitacion['numero']], $habitacion);
        }

        ConfiguracionSistema::updateOrCreate(
            ['clave' => 'valoraciones_post_salida'],
            [
                'valor' => [
                    'activo' => true,
                    'enviar_despues_de_minutos' => 30,
                    'canal' => 'correo',
                    'mensaje' => 'Gracias por visitarnos. Queremos conocer tu experiencia en DISCRET.',
                ],
                'descripcion' => 'Regla para enviar valoracion al cliente despues de la salida.',
            ],
        );
    }
}
