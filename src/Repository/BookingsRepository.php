<?php
namespace App\Repository;

use App\Entity\Booking;

class BookingsRepository
{
    private string $filePath;

    private const HEADERS = [
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
            fputcsv($handle, self::HEADERS);
            fclose($handle);
        }
    }

    public function findAllBookings(): array
    {
        $bookings = [];
        if (($handle = fopen($this->filePath, 'r')) !== false) {
            fgetcsv($handle, 1000, ',');
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $booking = (new Booking())
                    ->setId((int) $data[0])
                    ->setPhoneNumber((string) $data[1])
                    ->setHouseId((int) $data[2])
                    ->setComment((string) $data[3]);

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
        $id = 1;
        $bookings = $this->findAllBookings();
        if (!empty($bookings)) {
            $lastBooking = end($bookings);
            $id = (int) $lastBooking->getId() + 1;
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
        if (!in_array($mode, ['w', 'a'])) {
            throw new \InvalidArgumentException('Invalid mode. Use "w" or "a".');
        }

        $handle = fopen($this->filePath, $mode);

        if ($mode === 'w') {
            fputcsv($handle, self::HEADERS);
        }

        foreach ($bookings as $booking) {
            $houseData = [
                $booking->getId(),
                $booking->getPhoneNumber(),
                $booking->getHouseId(),
                $booking->getComment(),
            ];
            fputcsv($handle, $houseData);
        }
        fclose($handle);
    }
}
