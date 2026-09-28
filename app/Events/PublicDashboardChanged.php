<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PublicDashboardChanged implements ShouldBroadcast
{
    public function __construct(
        public readonly string $domain,
        public readonly ?int $year,
        public readonly ?int $cdkId,
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('dashboard.province')];
        if ($this->cdkId !== null) {
            $channels[] = new PrivateChannel('dashboard.cdk.' . $this->cdkId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'dashboard.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'domain' => $this->domain,
            'year' => $this->year,
            'cdkId' => $this->cdkId,
        ];
    }

    public function broadcastQueue(): string
    {
        return 'dashboard-broadcasts';
    }
}
