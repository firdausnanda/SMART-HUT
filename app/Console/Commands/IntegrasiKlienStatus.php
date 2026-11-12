<?php

namespace App\Console\Commands;

use App\Models\IntegrationClient;
use Illuminate\Console\Command;

class IntegrasiKlienStatus extends Command
{
    protected $signature = 'integrasi:klien-status
                            {kode : Kode klien}
                            {status : aktif atau nonaktif}';

    protected $description = 'Aktifkan atau nonaktifkan klien integrasi tanpa mencabut tokennya.';

    public function handle(): int
    {
        $status = $this->argument('status');
        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            $this->error('Status harus "aktif" atau "nonaktif".');
            return self::FAILURE;
        }

        $client = IntegrationClient::where('kode', $this->argument('kode'))->first();
        if (!$client) {
            $this->error('Klien "' . $this->argument('kode') . '" tidak ditemukan.');
            return self::FAILURE;
        }

        $client->update(['is_active' => $status === 'aktif']);
        $this->info("Klien \"{$client->kode}\" sekarang $status.");

        return self::SUCCESS;
    }
}
