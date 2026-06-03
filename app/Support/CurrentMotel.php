<?php

namespace App\Support;

use App\Models\Motel;
use Illuminate\Http\Request;

class CurrentMotel
{
    public static function resolve(Request $request): Motel
    {
        if ($request->filled('id_motel')) {
            return Motel::where('id_motel', $request->integer('id_motel'))->firstOrFail();
        }

        if ($request->filled('motel_slug')) {
            return Motel::where('slug', $request->string('motel_slug'))->firstOrFail();
        }

        return Motel::where('activo', true)->firstOrFail();
    }

    public static function id(Request $request): int
    {
        return self::resolve($request)->id_motel;
    }
}
