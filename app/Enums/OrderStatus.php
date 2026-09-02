<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }
}
