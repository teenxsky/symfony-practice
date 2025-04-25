<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class DeserializeContentException extends RuntimeException
{
    private int $statusCode;

    public function __construct(string $message = 'Unsupported content format', int $statusCode = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
