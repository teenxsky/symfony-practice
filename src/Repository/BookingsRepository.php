<?php
namespace App\Repository;

use App\Entity\Booking;

class BookingsRepository
{
    private string $file_path;

    private const HEADERS = [
        'id',
        'phone_number',
        'house_id',
        'comment',
    ];

    public function __construct(string $file_path)
    {
        $this->file_path = $file_path;

        if (! file_exists($this->file_path)) {
            $handle = fopen($this->file_path, 'w');
            fputcsv($handle, self::HEADERS);
            fclose($handle);
        }
    }

    public function findAllBookings(): array
    {
        $bookings = [];
        if (($handle = fopen($this->file_path, 'r')) !== false) {
            fgetcsv($handle, 1000, ',');
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $booking = new Booking();

                $booking
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
        $id       = 1;
        $bookings = $this->findAllBookings();
        if (! empty($bookings)) {
            $last_booking = end($bookings);
            $id           = (int) $last_booking->getId() + 1;
        }

        $booking->setId($id);

        $this->saveBookings([$booking], 'a');
    }

    public function updateBooking(Booking $booking): void
    {
        $bookings = $this->findAllBookings();
        foreach ($bookings as &$existing_booking) {
            if ($existing_booking->getId() == $booking->getId()) {
                $existing_booking = $booking;
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

        $handle = fopen($this->file_path, $mode);

        if ($mode === 'w') {
            fputcsv($handle, self::HEADERS);
        }

        foreach ($bookings as $booking) {
            $house_data = [
                $booking->getId(),
                $booking->getPhoneNumber(),
                $booking->getHouseId(),
                $booking->getComment(),
            ];
            fputcsv($handle, $house_data);
        }
        fclose($handle);
    }
}
