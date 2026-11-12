<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catat setiap permintaan klien integrasi ke activity_log (log_name "integrasi").
 * Pencatatan dilakukan setelah respons terkirim agar tidak menambah latensi.
 */
class LogIntegrationRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('integrasi_started_at', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $client = $request->user();

        if (!config('integrasi.log_requests') || !$client instanceof IntegrationClient) {
            return;
        }

        $startedAt = $request->attributes->get('integrasi_started_at', microtime(true));

        activity('integrasi')
            ->causedBy($client)
            ->event('api_request')
            ->withProperties([
                'method' => $request->method(),
                'path' => $request->path(),
                'query' => $request->query(),
                'status' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'token_id' => $client->currentAccessToken()?->id,
                'ip' => $request->ip(),
            ])
            ->log("{$request->method()} /{$request->path()}");
    }
}
