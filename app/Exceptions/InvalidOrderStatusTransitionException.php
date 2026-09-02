<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidOrderStatusTransitionException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The requested order status transition is not allowed.');
    }

    public function status(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function errorCode(): string
    {
        return 'invalid_order_status_transition';
    }
}
