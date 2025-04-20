<?php
namespace App\Constant;

/**
 * Class ApiMessages
 * @package App\Constant
 *
 * This class contains messages related to API responses.
 */
class ApiMessages
{
    /**
     * @return array{message: string, errors?: array}
     */
    public static function buildMessage(string $message, array $errors = []): array
    {
        return ! empty($errors)
        ? [
            'message' => $message,
            'errors'  => $errors,
        ]
        : [
            'message' => $message,
        ];
    }
}
