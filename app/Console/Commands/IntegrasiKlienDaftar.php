<?php

namespace App\Console\Commands;

use App\Models\IntegrationClient;
use Illuminate\Console\Command;

class IntegrasiKlienDaftar extends Command
{
    protected $signature = 'integrasi:klien-daftar';

    protected $description = 'Tampilkan klien integrasi beserta token aktifnya.';

    public function handle(): int
    {
        $clients = IntegrationClient::with(['cdk:id,nama', 'tokens'])->orderBy('kode')->get();

        if ($clients->isEmpty()) {
            $this->info('Belum ada klien integrasi.');
            return self::SUCCESS;
        }

        $this->table(
            ['Kode', 'Nama', 'Cakupan', 'Status', 'Token', 'Terakhir dipakai'],
            $clients->map(fn (IntegrationClient $client) => [
                $client->kode,
                $client->nama,
                $client->cdk?->nama ?? 'Seluruh provinsi',
                $client->is_active ? 'aktif' : 'nonaktif',
                $client->tokens->map(fn ($token) => "#{$token->id} {$token->name} [" . implode(', ', $token->abilities) . ']')->implode("\n") ?: '-',
                $client->tokens->max('last_used_at')?->toDateTimeString() ?? '-',
            ])
        );

        return self::SUCCESS;
    }
}
