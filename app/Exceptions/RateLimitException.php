<?php

namespace App\Exceptions;

use Exception;

class RateLimitException extends Exception
{
    public function __construct(string $message = 'Trakt API rate limit exceeded.', int $code = 429, public int $retryAfter = 60)
    {
        parent::__construct($message, $code);
    }
}
