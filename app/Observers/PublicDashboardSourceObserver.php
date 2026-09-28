<?php

namespace App\Observers;

use App\Services\PublicDashboardRealtime;
use Illuminate\Database\Eloquent\Model;

class PublicDashboardSourceObserver
{
    private static ?\WeakMap $originalAttributes = null;

    public function saving(Model $model): void
    {
        self::$originalAttributes ??= new \WeakMap();
        self::$originalAttributes[$model] = $model->exists ? $model->getRawOriginal() : null;
    }

    public function saved(Model $model): void
    {
        $before = self::$originalAttributes[$model] ?? null;
        unset(self::$originalAttributes[$model]);
        app(PublicDashboardRealtime::class)->recordChange($model, $before, $model->getAttributes());
    }

    public function deleted(Model $model): void
    {
        app(PublicDashboardRealtime::class)->recordChange($model, $model->getRawOriginal(), null);
    }
}
