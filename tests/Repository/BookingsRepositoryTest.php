<?php
namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Repository\BookingsRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BookingsRepositoryTest extends TestCase
{
    private string $filePath = __DIR__ . '/../Resources/~$test_bookings.csv';

    /** @var Booking[] */
    private array $bookings = [];
    /** @var BookingsRepository $bookingsRepository */
    private BookingsRepository $bookingsRepository;

    protected function setUp(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
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

        $this->bookingsRepository = new BookingsRepository($this->filePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        $this->bookings = [];
    }

    private function assertBookingEquals(Booking $expected, Booking $actual): void
    {
        $this->assertEquals(
            $expected->getId(),
            $actual->getId()
        );
        $this->assertEquals(
            $expected->getHouseId(),
            $actual->getHouseId()
        );
        $this->assertEquals(
            $expected->getPhoneNumber(),
            $actual->getPhoneNumber()
        );
        $this->assertEquals(
            $expected->getComment(),
            $actual->getComment()
        );
    }

    public function testAddBooking(): void
    {
        $bookingId       = 1;
        $expectedBooking = $this->bookings[$bookingId - 1];

        $this->bookingsRepository->addBooking($expectedBooking);

        $this->assertCount(
            1,
            $this->bookingsRepository->findAllBookings()
        );

        $actualBooking = $this->bookingsRepository->findBookingById($bookingId);

        $this->assertBookingEquals(
            expected: $expectedBooking,
            actual: $actualBooking
        );
    }

    public function testFindAllBookings(): void
    {
        $countBefore = 0;
        $countAfter  = count($this->bookings);

        $this->assertCount(
            $countBefore,
            $this->bookingsRepository->findAllBookings()
        );

        foreach ($this->bookings as $booking) {
            $this->bookingsRepository->addBooking($booking);
        }

        $bookings = $this->bookingsRepository->findAllBookings();

        $this->assertCount(
            $countAfter,
            $bookings
        );
        for ($i = 0; $i < $countAfter; $i++) {
            $this->assertBookingEquals(
                expected: $this->bookings[$i],
                actual: $bookings[$i]
            );
        }
    }

    public function testFindBookingById(): void
    {
        $bookingId       = 2;
        $expectedBooking = $this->bookings[$bookingId - 1];

        foreach ($this->bookings as $booking) {
            $this->bookingsRepository->addBooking($booking);
        }

        $actualBooking = $this->bookingsRepository->findBookingById($bookingId);

        $this->assertNotNull($actualBooking);
        $this->assertBookingEquals(
            expected: $expectedBooking,
            actual: $actualBooking
        );
    }

    public function testUpdateBooking(): void
    {
        $bookingId = 2;

        foreach ($this->bookings as $booking) {
            $this->bookingsRepository->addBooking($booking);
        }

        # Before updating booking
        $expectedBooking = $this->bookings[$bookingId - 1];

        $actualBooking = $this->bookingsRepository->findBookingById($bookingId);

        $this->assertNotNull($actualBooking);
        $this->assertBookingEquals(
            expected: $expectedBooking,
            actual: $actualBooking
        );

        # After updating booking
        $expectedBooking = $this->bookings[$bookingId - 1];
        $expectedBooking->setPhoneNumber("+5555555555");
        $expectedBooking->setHouseId(1);
        $expectedBooking->setComment("Test comment 1");

        $this->bookingsRepository->updateBooking($expectedBooking);

        $actualBooking = $this->bookingsRepository->findBookingById($bookingId);

        $this->assertNotNull($actualBooking);
        $this->assertBookingEquals(
            expected: $expectedBooking,
            actual: $actualBooking
        );
    }

    public function testDeleteBooking(): void
    {
        $bookingId   = 2;
        $countBefore = count($this->bookings);
        $countAfter  = count($this->bookings) - 1;

        foreach ($this->bookings as $booking) {
            $this->bookingsRepository->addBooking($booking);
        }

        $this->assertCount(
            $countBefore,
            $this->bookingsRepository->findAllBookings()
        );

        $this->bookingsRepository->deleteBooking($bookingId);

        $this->assertCount(
            $countAfter,
            $this->bookingsRepository->findAllBookings()
        );
        $this->assertNull(
            $this->bookingsRepository->findBookingById($bookingId)
        );
    }

    public function testSaveBookingsPrivate(): void
    {
        $methodName = 'saveBookings';

        $reflection = new ReflectionClass(
            $this->bookingsRepository
        );
        $method = $reflection->getMethod($methodName);

        $this->assertTrue(
            method_exists(
                $this->bookingsRepository,
                $methodName
            ),
        );
        $this->assertTrue($method->isPrivate());
    }
}
