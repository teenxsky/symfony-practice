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
        'comment'
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
                $booking = new Booking();
                $booking->setId((int)$data[0]);
                $booking->setPhoneNumber((string)$data[1]);
                $booking->setHouseId((int)$data[2]);
                $booking->setComment((string)$data[3]);

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
            $id = (int)$lastBooking->getId() + 1;
        }

        $booking->setId($id);

        $bookingData = [
            $booking->getId(),
            $booking->getPhoneNumber(),
            $booking->getHouseId(),
            $booking->getComment(),
        ];

        $handle = fopen($this->filePath, 'a');
        fputcsv($handle, $bookingData);
        fclose($handle);
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
        
        $handle = fopen($this->filePath, 'w');
        fputcsv($handle, self::HEADERS);
        foreach ($bookings as $booking) {
            $bookingData = [
                $booking->getId(),
                $booking->getPhoneNumber(),
                $booking->getHouseId(),
                $booking->getComment(),
            ];
            fputcsv($handle, $bookingData);
        }
        fclose($handle);
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

        $handle = fopen($this->filePath, 'w');
        fputcsv($handle, self::HEADERS);
        foreach ($bookings as $booking) {
            $bookingData = [
                $booking->getId(),
                $booking->getPhoneNumber(),
                $booking->getHouseId(),
                $booking->getComment(),
            ];
            fputcsv($handle, $bookingData);
        }
        fclose($handle);
    }
}