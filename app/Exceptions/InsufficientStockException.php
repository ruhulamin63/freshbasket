<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InsufficientStockException extends DomainException
{
    public function __construct(public readonly string $itemName)
    {
        parent::__construct("Insufficient stock for {$itemName}.");
    }

    public function status(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function errorCode(): string
    {
        return 'insufficient_stock';
    }
}
