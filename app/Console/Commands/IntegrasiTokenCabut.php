<?php

namespace App\Console\Commands;

use App\Models\IntegrationClient;
use App\Services\IntegrationTokenService;
use Illuminate\Console\Command;

class IntegrasiTokenCabut extends Command
{
    protected $signature = 'integrasi:token-cabut
                            {kode : Kode klien}
                            {--token= : ID token yang dicabut (lihat integrasi:klien-daftar)}
                            {--semua : Cabut semua token klien ini}';

    protected $description = 'Cabut token API klien integrasi.';

    public function handle(IntegrationTokenService $tokens): int
    {
        $client = IntegrationClient::where('kode', $this->argument('kode'))->first();
        if (!$client) {
            $this->error('Klien "' . $this->argument('kode') . '" tidak ditemukan.');
            return self::FAILURE;
        }

        if ($this->option('semua') === ($this->option('token') !== null)) {
            $this->error('Pilih salah satu: --token=ID atau --semua.');
            return self::FAILURE;
        }

        $ids = $tokens->revoke($client, $this->option('semua') ? null : [(int) $this->option('token')]);
        if (empty($ids)) {
            $this->error('Token tidak ditemukan untuk klien ini.');
            return self::FAILURE;
        }

        $this->info(count($ids) . " token dicabut dari \"{$client->kode}\".");

        return self::SUCCESS;
    }
}
