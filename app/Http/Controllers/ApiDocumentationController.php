<?php

namespace App\Http\Controllers;

use App\Support\Integrasi\ApiDocumentation;
use Inertia\Inertia;

/**
 * Halaman dokumentasi API integrasi untuk pengembang sistem klien.
 * Publik: tidak memuat rahasia, akses data tetap dijaga oleh token.
 */
class ApiDocumentationController extends Controller
{
    public function show()
    {
        return Inertia::render('Docs/Api', [
            'version' => ApiDocumentation::VERSION,
            'baseUrl' => url('/api/' . ApiDocumentation::VERSION),
            'endpoints' => ApiDocumentation::endpoints(),
            'errors' => ApiDocumentation::errors(),
            'changelog' => ApiDocumentation::changelog(),
            'abilities' => collect(config('integrasi.abilities'))
                ->map(fn ($label, $key) => ['value' => $key, 'label' => $label])
                ->values(),
            'rateLimitPerMinute' => config('integrasi.rate_limit_per_minute'),
        ]);
    }
}
