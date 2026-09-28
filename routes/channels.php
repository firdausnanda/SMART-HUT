<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard.province', fn (User $user) => $user->isAdminProvinsi());
Broadcast::channel('dashboard.cdk.{cdkId}', fn (User $user, $cdkId) =>
    $user->isAdminProvinsi() || (int) $user->cdk_id === (int) $cdkId
);
