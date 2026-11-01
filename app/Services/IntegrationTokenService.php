<?php

namespace App\Services;

use App\Models\IntegrationClient;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\NewAccessToken;

/**
 * Penerbitan dan pencabutan token klien integrasi, dipakai bersama oleh
 * perintah artisan integrasi:* dan halaman admin Klien Integrasi.
 */
class IntegrationTokenService
{
    public function knownAbilities(): array
    {
        return array_keys(config('integrasi.abilities'));
    }

    public function issue(
        IntegrationClient $client,
        string $name,
        array $abilities,
        ?CarbonInterface $expiresAt = null,
        ?Model $causer = null,
    ): NewAccessToken {
        $abilities = array_values(array_unique($abilities));
        $token = $client->createToken($name, $abilities, $expiresAt);

        activity('integrasi')
            ->performedOn($client)
            ->causedBy($causer)
            ->event('token_created')
            ->withProperties(['token_id' => $token->accessToken->id, 'abilities' => $abilities, 'expires_at' => $expiresAt?->toIso8601String()])
            ->log("Token #{$token->accessToken->id} diterbitkan untuk {$client->kode}");

        return $token;
    }

    /**
     * @param  array<int>|null  $tokenIds  null = cabut semua token klien
     * @return array<int> ID token yang dicabut
     */
    public function revoke(IntegrationClient $client, ?array $tokenIds = null, ?Model $causer = null): array
    {
        $query = $client->tokens();
        if ($tokenIds !== null) {
            $query->whereKey($tokenIds);
        }

        $ids = $query->pluck('id')->all();
        if (empty($ids)) {
            return [];
        }

        $client->tokens()->whereKey($ids)->delete();

        activity('integrasi')
            ->performedOn($client)
            ->causedBy($causer)
            ->event('token_revoked')
            ->withProperties(['token_ids' => $ids])
            ->log('Token #' . implode(', #', $ids) . " dicabut dari {$client->kode}");

        return $ids;
    }
}
