<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationClientController extends Controller
{
    /**
     * Identitas klien pemilik token, cakupan wilayah, dan ability token.
     * Dipakai klien untuk memeriksa koneksi dan konfigurasi token.
     */
    public function show(Request $request): JsonResponse
    {
        $client = $request->user()->loadMissing('cdk:id,kode,nama');
        $token = $client->currentAccessToken();

        return response()->json([
            'data' => [
                'kode' => $client->kode,
                'nama' => $client->nama,
                'cakupan' => $client->isProvinceWide() ? 'provinsi' : 'cdk',
                'cdk' => $client->cdk ? [
                    'id' => $client->cdk->id,
                    'kode' => $client->cdk->kode,
                    'nama' => $client->cdk->nama,
                ] : null,
                'token' => [
                    'nama' => $token->name,
                    'abilities' => $token->abilities,
                    'kedaluwarsa' => $token->expires_at?->toIso8601String(),
                ],
                'waktu_server' => now()->toIso8601String(),
            ],
        ]);
    }
}
