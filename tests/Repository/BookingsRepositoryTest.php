<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Entity\House;
use App\Repository\BookingsRepository;
use App\Repository\HousesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookingsRepositoryTest extends KernelTestCase
{
    /** @var Booking[] */
    private array $bookings = [];

    /** @var HousesRepository $housesRepository */
    private HousesRepository $housesRepository;
    /** @var BookingsRepository $bookingsRepository */
    private BookingsRepository $bookingsRepository;

    private EntityManagerInterface $entityManager;

    private const HOUSES_CSV_PATH = __DIR__ . '/../Resources/test_houses.csv';

    #[Override]
    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->truncateTables();

        $this->bookingsRepository = $this->entityManager->getRepository(Booking::class);
        $this->housesRepository   = $this->entityManager->getRepository(House::class);
        $this->housesRepository->loadFromCsv(self::HOUSES_CSV_PATH);

        $this->bookings[] = (new Booking())
            ->setId(1)
            ->setPhoneNumber('+1234567890')
            ->setComment('Test comment 1')
            ->setHouse(
                $this->housesRepository->findHouseById(1)
            );
        $this->bookings[] = (new Booking())
            ->setId(2)
            ->setPhoneNumber('+0987654321')
            ->setHouse(
                $this->housesRepository->findHouseById(2)
            );
        $this->bookings[] = (new Booking())
            ->setId(3)
            ->setPhoneNumber('+1122334455')
            ->setComment('Test comment 3')
            ->setHouse(
                $this->housesRepository->findHouseById(3)
            );
    }

    private function truncateTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE booking RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE TABLE house RESTART IDENTITY CASCADE');
    }

    private function assertBookingEquals(Booking $expected, Booking $actual): void
    {
        $this->assertEquals(
            $expected->getId(),
            $actual->getId()
        );
        $this->assertEquals(
            $expected->getHouse()->getId(),
            $actual->getHouse()->getId()
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

        // Before updating booking
        $expectedBooking = $this->bookings[$bookingId - 1];

        $actualBooking = $this->bookingsRepository->findBookingById($bookingId);

        $this->assertNotNull($actualBooking);
        $this->assertBookingEquals(
            expected: $expectedBooking,
            actual: $actualBooking
        );

        // After updating booking
        $expectedBooking = $this->bookings[$bookingId - 1];
        $expectedBooking
            ->setPhoneNumber('+5555555555')
            ->setComment('Test comment 1')
            ->setHouse(
                $this->housesRepository->findHouseById(1)
            );

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

        $this->bookingsRepository->deleteBookingById($bookingId);

        $this->assertCount(
            $countAfter,
            $this->bookingsRepository->findAllBookings()
        );
        $this->assertNull(
            $this->bookingsRepository->findBookingById($bookingId)
        );
    }

    public function testLoadFromCsv(): void
    {
        $countBefore = 0;
        $countAfter  = count($this->bookings);

        $this->assertCount(
            $countBefore,
            $this->bookingsRepository->findAllBookings()
        );

        // Create a CSV file with test data
        $bookingsCsvPath = __DIR__ . '/../Resources/~$test_bookings.csv';
        $handle          = fopen($bookingsCsvPath, 'w');
        fputcsv(
            $handle,
            $this->bookingsRepository->getFields(),
            ',',
            '"',
            '\\'
        );

        foreach ($this->bookings as $booking) {
            fputcsv(
                $handle,
                $booking->toArray(),
                ',',
                '"',
                '\\'
            );
        }
        fclose($handle);

        // Load the CSV file into the repository
        $this->bookingsRepository->loadFromCsv($bookingsCsvPath);

        // Check that the bookings were loaded correctly
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
}
