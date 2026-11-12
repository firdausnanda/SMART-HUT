<?php

namespace App\Http\Controllers;

use App\Models\Cdk;
use App\Models\IntegrationClient;
use App\Services\IntegrationTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Halaman admin untuk mengelola klien API integrasi dan token-nya.
 * Hanya role admin (lihat routes/web.php).
 */
class IntegrasiKlienController extends Controller
{
    public function __construct(private IntegrationTokenService $tokens)
    {
    }

    public function index()
    {
        $clients = IntegrationClient::with([
            'cdk:id,nama',
            'tokens' => fn ($query) => $query->latest(),
        ])->orderBy('nama')->get();

        $usage = DB::table('activity_log')
            ->where('log_name', 'integrasi')
            ->where('event', 'api_request')
            ->where('causer_type', IntegrationClient::class)
            ->whereIn('causer_id', $clients->modelKeys())
            ->groupBy('causer_id')
            ->selectRaw('causer_id, MAX(created_at) as last_request_at, SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as requests_24h', [now()->subDay()])
            ->get()
            ->keyBy('causer_id');

        return Inertia::render('IntegrasiKlien/Index', [
            'clients' => $clients->map(fn (IntegrationClient $client) => [
                'id' => $client->id,
                'kode' => $client->kode,
                'nama' => $client->nama,
                'deskripsi' => $client->deskripsi,
                'cdk_id' => $client->cdk_id,
                'cdk_nama' => $client->cdk?->nama,
                'is_active' => $client->is_active,
                'last_request_at' => $usage[$client->id]->last_request_at ?? null,
                'requests_24h' => (int) ($usage[$client->id]->requests_24h ?? 0),
                'tokens' => $client->tokens->map(fn (PersonalAccessToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'is_expired' => $token->expires_at?->isPast() ?? false,
                    'created_at' => $token->created_at?->toIso8601String(),
                ])->values(),
            ]),
            'cdks' => Cdk::orderBy('nama')->get(['id', 'nama']),
            'abilities' => collect(config('integrasi.abilities'))
                ->map(fn ($label, $key) => ['value' => $key, 'label' => $label])
                ->values(),
            'rateLimitPerMinute' => config('integrasi.rate_limit_per_minute'),
        ]);
    }

    public function store(Request $request)
    {
        IntegrationClient::create($this->validateClient($request));

        return redirect()->route('integrasi-klien.index')->with('success', 'Klien integrasi berhasil ditambahkan.');
    }

    public function update(Request $request, IntegrationClient $integrasiKlien)
    {
        $integrasiKlien->update($this->validateClient($request, $integrasiKlien));

        return redirect()->route('integrasi-klien.index')->with('success', 'Klien integrasi berhasil diperbarui.');
    }

    public function destroy(Request $request, IntegrationClient $integrasiKlien)
    {
        DB::transaction(function () use ($request, $integrasiKlien) {
            $this->tokens->revoke($integrasiKlien, null, $request->user());
            $integrasiKlien->delete();
        });

        return redirect()->route('integrasi-klien.index')->with('success', 'Klien integrasi dan semua tokennya dihapus.');
    }

    public function storeToken(Request $request, IntegrationClient $integrasiKlien)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in($this->tokens->knownAbilities())],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:1825'],
        ], [
            'abilities.required' => 'Pilih minimal satu akses data.',
            'abilities.min' => 'Pilih minimal satu akses data.',
        ]);

        $expiresAt = isset($validated['expires_in_days']) ? now()->addDays($validated['expires_in_days']) : null;
        $token = $this->tokens->issue($integrasiKlien, $validated['name'], $validated['abilities'], $expiresAt, $request->user());

        // Token mentah hanya dikirim sekali lewat flash; yang tersimpan di DB hanya hash-nya.
        return redirect()->route('integrasi-klien.index')
            ->with('success', "Token untuk {$integrasiKlien->nama} berhasil diterbitkan.")
            ->with('new_token', [
                'client' => $integrasiKlien->nama,
                'name' => $token->accessToken->name,
                'token' => $token->plainTextToken,
            ]);
    }

    public function destroyToken(Request $request, IntegrationClient $integrasiKlien, int $token)
    {
        $revoked = $this->tokens->revoke($integrasiKlien, [$token], $request->user());
        abort_if(empty($revoked), 404);

        return redirect()->route('integrasi-klien.index')->with('success', 'Token berhasil dicabut.');
    }

    private function validateClient(Request $request, ?IntegrationClient $client = null): array
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('integration_clients', 'kode')->ignore($client)],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'cdk_id' => ['nullable', 'integer', 'exists:cdks,id'],
            'is_active' => ['required', 'boolean'],
        ], [
            'kode.regex' => 'Kode hanya boleh huruf kecil, angka, dan tanda hubung (mis. cdk-trenggalek).',
        ]);

        return $validated;
    }
}
