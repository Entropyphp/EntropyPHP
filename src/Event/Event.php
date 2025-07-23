<?php

declare(strict_types=1);

namespace Entropy\Event;

use League\Event\HasEventName;

class Event implements HasEventName
{
    public const NAME = '';

    public function eventName(): string
    {
        return static::NAME;
    }
}
