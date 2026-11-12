<?php

namespace Modules\Rhl\App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\IntegrationResource;
use Illuminate\Http\Request;

/**
 * Data RHL berbasis luas (ha): rehabilitasi lahan, penghijauan lingkungan,
 * dan rehabilitasi mangrove.
 */
class RhlLuasResource extends IntegrationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->common(
                $this->whenLoadedModel('province_rel'),
                $this->whenLoadedModel('regency_rel'),
                $this->whenLoadedModel('district_rel'),
                $this->whenLoadedModel('village_rel'),
            ),
            ...$this->extra(),
            'target_tahunan_ha' => $this->decimal($this->target_annual),
            'realisasi_ha' => $this->decimal($this->realization),
            'sumber_dana' => $this->fund_source,
            'koordinat' => $this->coordinates,
            ...$this->timestamps(),
        ];
    }

    /**
     * Field tambahan khusus dataset, disisipkan setelah wilayah.
     */
    protected function extra(): array
    {
        return [];
    }
}
