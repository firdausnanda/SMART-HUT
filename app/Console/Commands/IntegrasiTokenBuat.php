<?php

namespace App\Console\Commands;

use App\Models\IntegrationClient;
use App\Services\IntegrationTokenService;
use Illuminate\Console\Command;

class IntegrasiTokenBuat extends Command
{
    protected $signature = 'integrasi:token-buat
                            {kode : Kode klien}
                            {--ability=* : Ability token, mis. --ability=rhl.read --ability=wilayah.read}
                            {--nama=default : Nama token, mis. produksi atau staging}
                            {--hari= : Masa berlaku token dalam hari. Kosongkan agar tidak kedaluwarsa}';

    protected $description = 'Terbitkan token API untuk klien integrasi. Token hanya ditampilkan sekali.';

    public function handle(IntegrationTokenService $tokens): int
    {
        $client = IntegrationClient::where('kode', $this->argument('kode'))->first();
        if (!$client) {
            $this->error('Klien "' . $this->argument('kode') . '" tidak ditemukan.');
            return self::FAILURE;
        }

        $known = $tokens->knownAbilities();
        $abilities = array_values(array_unique($this->option('ability')));

        if (empty($abilities)) {
            $this->error('Sebutkan minimal satu --ability. Pilihan: ' . implode(', ', $known));
            return self::FAILURE;
        }

        $unknown = array_diff($abilities, $known);
        if ($unknown) {
            $this->error('Ability tidak dikenal: ' . implode(', ', $unknown) . '. Pilihan: ' . implode(', ', $known));
            return self::FAILURE;
        }

        $hari = $this->option('hari');
        if ($hari !== null && (!ctype_digit((string) $hari) || (int) $hari < 1)) {
            $this->error('--hari harus bilangan bulat positif.');
            return self::FAILURE;
        }

        $expiresAt = $hari !== null ? now()->addDays((int) $hari) : null;
        $token = $tokens->issue($client, $this->option('nama'), $abilities, $expiresAt);

        $this->info("Token #{$token->accessToken->id} untuk \"{$client->kode}\" diterbitkan.");
        $this->line('Ability    : ' . implode(', ', $abilities));
        $this->line('Kedaluwarsa: ' . ($expiresAt?->toDateTimeString() ?? 'tidak ada'));
        $this->newLine();
        $this->warn('Simpan token ini sekarang. Token tidak bisa ditampilkan lagi:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
