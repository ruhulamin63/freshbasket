<?php

namespace App\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;
}
