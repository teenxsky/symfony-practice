<?php
namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class DeserializeContentException extends \RuntimeException
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
