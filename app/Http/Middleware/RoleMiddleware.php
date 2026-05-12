<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ← tambah ini

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check() || Auth::user()->role !== $role) // ← ganti auth() ke Auth::
        {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}