<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\HabitacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TipoHabitacionController;
use App\Http\Controllers\ValoracionController;
use App\Models\ConfiguracionSistema;
use App\Models\User;
use App\Support\CurrentMotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/ping', fn () => [
    'app' => 'DISCRET API',
    'status' => 'ok',
]);

Route::post('/admin/login', function (Request $request) {
    $data = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('email', $data['email'])->first();

    if (! $user || ! Hash::check($data['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Las credenciales de administrador no son correctas.'],
        ]);
    }

    return response()->json([
        'admin' => [
            'id' => $user->id,
            'id_motel' => $user->id_motel,
            'name' => $user->name,
            'email' => $user->email,
        ],
    ]);
});

Route::post('/clientes/login', [ClienteController::class, 'login']);
Route::apiResource('clientes', ClienteController::class);
Route::apiResource('tipos-habitacion', TipoHabitacionController::class)
    ->parameters(['tipos-habitacion' => 'tipoHabitacion']);
Route::apiResource('habitaciones', HabitacionController::class)
    ->parameters(['habitaciones' => 'habitacion']);
Route::apiResource('reservas', ReservaController::class);
Route::apiResource('reportes', ReporteController::class)
    ->only(['index', 'store', 'show', 'destroy'])
    ->parameters(['reportes' => 'reporte']);

Route::get('/valoraciones', [ValoracionController::class, 'index']);
Route::get('/valoraciones/{token}', [ValoracionController::class, 'showByToken']);
Route::post('/valoraciones/{token}', [ValoracionController::class, 'storeByToken']);

Route::get('/configuracion', fn (Request $request) => ConfiguracionSistema::where('id_motel', CurrentMotel::id($request))
    ->orderBy('clave')
    ->get());

Route::put('/configuracion/{clave}', function (Request $request, string $clave) {
    $data = $request->validate([
        'valor' => ['required', 'array'],
        'descripcion' => ['nullable', 'string', 'max:200'],
    ]);

    $idMotel = CurrentMotel::id($request);

    return ConfiguracionSistema::updateOrCreate(
        [
            'id_motel' => $idMotel,
            'clave' => $clave,
        ],
        $data,
    );
});
