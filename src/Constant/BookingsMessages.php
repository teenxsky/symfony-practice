<?php
namespace App\Constant;

use App\Constant\ApiMessages;

/**
 * Class BookingsMessages
 * @package App\Constant
 *
 * This class contains messages related to the Booking entity.
 */
class BookingsMessages extends ApiMessages
{
    public const CREATED   = 'Booking created!';
    public const REPLACED  = 'Booking replaced!';
    public const UPDATED   = 'Booking updated!';
    public const DELETED   = 'Booking deleted!';
    public const NOT_FOUND = 'Booking not found.';

    /**
     * @return array{message: string, errors?: array}
     */
    public static function created(): array
    {
        return self::buildMessage(self::CREATED);
    }

    /**
     * @return array{message: string, errors?: array}
     */
    public static function replaced(): array
    {
        return self::buildMessage(self::REPLACED);
    }

    /**
     * @return array{message: string, errors?: array}
     */
    public static function updated(): array
    {
        return self::buildMessage(self::UPDATED);
    }

    /**
     * @return array{message: string, errors?: array}
     */
    public static function deleted(): array
    {
        return self::buildMessage(self::DELETED);
    }

    /**
     * @return array{message: string, errors?: array}
     */
    public static function notFound(): array
    {
        return self::buildMessage(self::NOT_FOUND);
    }
}
