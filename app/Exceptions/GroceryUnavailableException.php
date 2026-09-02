<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class GroceryUnavailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('One or more grocery items are unavailable.');
    }

    public function status(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public function errorCode(): string
    {
        return 'grocery_unavailable';
    }
}
