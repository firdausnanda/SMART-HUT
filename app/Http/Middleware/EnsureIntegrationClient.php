<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya token milik klien integrasi aktif yang boleh mengakses /api/v1.
 */
class EnsureIntegrationClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user();

        if (!$client instanceof IntegrationClient) {
            return response()->json(['message' => 'Token bukan milik klien integrasi.'], 403);
        }

        if (!$client->is_active) {
            return response()->json(['message' => 'Klien integrasi dinonaktifkan.'], 403);
        }

        return $next($request);
    }
}
