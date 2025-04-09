<?php
namespace App\Repository;

use App\Entity\Booking;

class BookingsRepository
{
    private string $filePath;

    private const BOOKING_FIELDS = [
        'id',
        'phone_number',
        'house_id',
        'comment',
    ];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        if (! file_exists($this->filePath)) {
            $handle = fopen($this->filePath, 'w');
            fputcsv($handle, self::BOOKING_FIELDS, ',', '"', '\\');
            fclose($handle);
        }
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return self::BOOKING_FIELDS;
    }

    /**
     * @return Booking[]
     */
    public function findAllBookings(): array
    {
        $bookings = [];
        if (($handle = fopen($this->filePath, 'r')) !== false) {
            fgetcsv($handle, 1000, ',', '"', '\\');

            while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
                $row = array_combine(
                    keys: self::BOOKING_FIELDS,
                    values: $data
                );

                $booking = (new Booking())
                    ->setId((int) $row['id'])
                    ->setHouseId((int) $row['house_id'])
                    ->setComment((string) $row['comment'])
                    ->setPhoneNumber((string) $row['phone_number']);

                $bookings[] = $booking;
            }
            fclose($handle);
        }
        return $bookings;
    }

    public function findBookingById(int $id): ?Booking
    {
        $bookings = $this->findAllBookings();
        foreach ($bookings as $booking) {
            if ($booking->getId() == $id) {
                return $booking;
            }
        }
        return null;
    }

    public function addBooking(Booking $booking): void
    {
        $id       = 1;
        $bookings = $this->findAllBookings();
        if (! empty($bookings)) {
            $lastBooking = end($bookings);
            $id          = (int) $lastBooking->getId() + 1;
        }

        $booking->setId($id);

        $this->saveBookings([$booking], 'a');
    }

    public function updateBooking(Booking $booking): void
    {
        $bookings = $this->findAllBookings();
        foreach ($bookings as &$existingBooking) {
            if ($existingBooking->getId() == $booking->getId()) {
                $existingBooking = $booking;
                break;
            }
        }

        $this->saveBookings($bookings, 'w');
    }

    public function deleteBooking(int $id): void
    {
        $bookings = $this->findAllBookings();
        foreach ($bookings as $key => $booking) {
            if ($booking->getId() == $id) {
                unset($bookings[$key]);
                break;
            }
        }

        $this->saveBookings($bookings, 'w');
    }

    private function saveBookings(array $bookings, string $mode): void
    {
        if (! in_array($mode, ['w', 'a'])) {
            throw new \InvalidArgumentException('Invalid mode. Use "w" or "a".');
        }

        $handle = fopen($this->filePath, $mode);

        if ($mode === 'w') {
            fputcsv($handle, self::BOOKING_FIELDS, ',', '"', '\\');
        }

        foreach ($bookings as $booking) {
            $bookingData = [
                $booking->getId(),
                $booking->getPhoneNumber(),
                $booking->getHouseId(),
                $booking->getComment(),
            ];
            fputcsv($handle, $bookingData, ',', '"', '\\');
        }
        fclose($handle);
    }
}
