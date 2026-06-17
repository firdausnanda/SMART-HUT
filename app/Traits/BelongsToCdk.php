<?php

namespace App\Traits;

use App\Models\Cdk;

trait BelongsToCdk
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToCdk()
    {
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->cdk_id) {
                $model->cdk_id = $model->cdk_id ?? auth()->user()->cdk_id;
            }
        });

        static::addGlobalScope('cdk_scope', function ($builder) {
            if (auth()->check()) {
                $user = auth()->user();
                if (!$user->isAdminProvinsi()) {
                    $builder->where($builder->getModel()->getTable() . '.cdk_id', $user->cdk_id);
                }
            }
        });
    }

    /**
     * Scope query to filter by CDK.
     * Admin Provinsi (cdk_id is null) or guest will bypass this filter to see all records,
     * unless a specific cdkId parameter is supplied.
     */
    public function scopeForCdk($query, ?int $cdkId = null)
    {
        $user = auth()->user();
        
        if (!$user || $user->isAdminProvinsi()) {
            if ($cdkId) {
                return $query->where($this->getTable() . '.cdk_id', $cdkId);
            }
            return $query;
        }

        return $query->where($this->getTable() . '.cdk_id', $cdkId ?? $user->cdk_id);
    }

    /**
     * Get Cdk relation.
     */
    public function cdk()
    {
        return $this->belongsTo(Cdk::class);
    }
}
