<?php
namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Repository\BookingsRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BookingsRepositoryTest extends TestCase
{
    private $file_path = __DIR__ . '/../Resources/~$test_bookings.csv';
    private $bookings  = [];

    protected function setUp(): void
    {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }

        $this->bookings[] = (new Booking())
            ->setHouseId(1)
            ->setPhoneNumber("+1234567890")
            ->setComment('Test comment 1');

        $this->bookings[] = (new Booking())
            ->setHouseId(2)
            ->setPhoneNumber("+0987654321");

        $this->bookings[] = (new Booking())
            ->setHouseId(3)
            ->setPhoneNumber("+1122334455")
            ->setComment('Test comment 3');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }

        $this->bookings = [];
    }

    public function testFindAllBookings()
    {
        $repository = new BookingsRepository($this->file_path);

        $bookings = $repository->findAllBookings();
        $this->assertCount(0, $bookings);

        foreach ($this->bookings as $booking) {
            $repository->addBooking($booking);
        }

        $bookings = $repository->findAllBookings();
        $this->assertCount(3, $bookings);
    }

    public function testFindBookingById()
    {
        $repository = new BookingsRepository($this->file_path);
        foreach ($this->bookings as $booking) {
            $repository->addBooking($booking);
        }

        $booking = $repository->findBookingById(2);
        $this->assertNotNull($booking);
        $this->assertEquals(2, $booking->getId());
        $this->assertEquals(2, $booking->getHouseId());
        $this->assertEquals("+0987654321", $booking->getPhoneNumber());
        $this->assertEquals("", $booking->getComment());

        $not_found_booking = $repository->findBookingById(999);
    }

    public function testAddBooking()
    {
        $repository = new BookingsRepository($this->file_path);
        $repository->addBooking($this->bookings[0]);

        $bookings = $repository->findAllBookings();
        $this->assertCount(1, $bookings);
        $this->assertEquals(1, $bookings[0]->getId());
        $this->assertEquals(1, $bookings[0]->getHouseId());
        $this->assertEquals("+1234567890", $bookings[0]->getPhoneNumber());
        $this->assertEquals("Test comment 1", $bookings[0]->getComment());
    }

    public function testUpdateBooking()
    {
        $repository = new BookingsRepository($this->file_path);

        $booking = $this->bookings[2];
        $repository->addBooking($booking);

        $uploaded_booking = $repository->findBookingById(1);
        $this->assertNotNull($uploaded_booking);
        $this->assertEquals(3, $uploaded_booking->getHouseId());
        $this->assertEquals("+1122334455", $uploaded_booking->getPhoneNumber());
        $this->assertEquals("Test comment 3", $uploaded_booking->getComment());

        $booking->setPhoneNumber("+5555555555");
        $booking->setHouseId(1);
        $booking->setComment("Test comment 1");
        $repository->updateBooking($booking);

        $updated_booking = $repository->findBookingById(1);
        $this->assertNotNull($updated_booking);
        $this->assertEquals(1, $updated_booking->getHouseId());
        $this->assertEquals("+5555555555", $updated_booking->getPhoneNumber());
        $this->assertEquals("Test comment 1", $updated_booking->getComment());
    }

    public function testDeleteBooking()
    {
        $repository = new BookingsRepository($this->file_path);

        foreach ($this->bookings as $booking) {
            $repository->addBooking($booking);
        }

        $bookings = $repository->findAllBookings();
        $this->assertCount(3, $bookings);
        $this->assertEquals(1, $bookings[0]->getId());
        $this->assertEquals(2, $bookings[1]->getId());
        $this->assertEquals(3, $bookings[2]->getId());

        $repository->deleteBooking(2);

        $bookings = $repository->findAllBookings();
        $this->assertCount(2, $bookings);
        $this->assertEquals(1, $bookings[0]->getId());
        $this->assertNotEquals(2, $bookings[1]->getId());
        $this->assertEquals(3, $bookings[1]->getId());
    }

    public function testSaveBookingsPrivate()
    {
        $repository = new BookingsRepository($this->file_path);

        $reflection = new ReflectionClass($repository);
        $method     = $reflection->getMethod('saveBookings');
        $this->assertTrue($method->isPrivate());
    }
}
