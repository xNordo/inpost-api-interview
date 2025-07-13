<?php

namespace Exception;

class ApiException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $message = 'API nie zwróciło poprawnej odpowiedzi, treść błędu: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}