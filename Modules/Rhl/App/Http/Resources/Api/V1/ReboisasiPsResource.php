<?php

namespace Modules\Rhl\App\Http\Resources\Api\V1;

/**
 * Reboisasi area Perhutanan Sosial: data luas (ha) ditambah pengelola PS.
 */
class ReboisasiPsResource extends RhlLuasResource
{
    protected function extra(): array
    {
        return [
            'pengelola' => $this->ref($this->whenLoadedModel('pengelola')),
        ];
    }
}
