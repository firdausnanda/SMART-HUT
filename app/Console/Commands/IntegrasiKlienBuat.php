<?php

namespace App\Console\Commands;

use App\Models\Cdk;
use App\Models\IntegrationClient;
use Illuminate\Console\Command;

class IntegrasiKlienBuat extends Command
{
    protected $signature = 'integrasi:klien-buat
                            {kode : Kode unik klien, mis. sipetak atau cdk-trenggalek}
                            {nama : Nama sistem klien}
                            {--cdk= : Batasi data ke CDK ini (id atau kode CDK). Kosongkan untuk seluruh provinsi}
                            {--deskripsi= : Keterangan singkat}';

    protected $description = 'Daftarkan klien integrasi API baru.';

    public function handle(): int
    {
        $kode = $this->argument('kode');

        if (IntegrationClient::where('kode', $kode)->exists()) {
            $this->error("Klien dengan kode \"$kode\" sudah ada.");
            return self::FAILURE;
        }

        $cdk = null;
        if ($this->option('cdk') !== null) {
            $cdk = ctype_digit($this->option('cdk'))
                ? Cdk::find($this->option('cdk'))
                : Cdk::where('kode', $this->option('cdk'))->first();
            if (!$cdk) {
                $this->error('CDK "' . $this->option('cdk') . '" tidak ditemukan.');
                return self::FAILURE;
            }
        }

        $client = IntegrationClient::create([
            'kode' => $kode,
            'nama' => $this->argument('nama'),
            'deskripsi' => $this->option('deskripsi'),
            'cdk_id' => $cdk?->id,
        ]);

        $this->info("Klien \"{$client->kode}\" dibuat dengan cakupan " . ($cdk ? $cdk->nama : 'seluruh provinsi') . '.');
        $this->line("Terbitkan token: php artisan integrasi:token-buat {$client->kode} --ability=...");

        return self::SUCCESS;
    }
}
