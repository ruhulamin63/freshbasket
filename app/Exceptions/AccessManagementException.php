<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class AccessManagementException extends DomainException
{
    public function __construct(string $message, private readonly string $errorCode = 'access_management_conflict')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
