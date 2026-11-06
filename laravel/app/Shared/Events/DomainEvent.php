<?php

namespace App\Shared\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use DateTimeImmutable;

abstract class DomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly DateTimeImmutable $occurredAt;
    public readonly string $eventId;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
        $this->eventId = (string) \Illuminate\Support\Str::uuid();
    }
}

