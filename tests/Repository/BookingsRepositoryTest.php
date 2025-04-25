<?php

declare (strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Entity\House;
use App\Repository\BookingsRepository;
use App\Repository\HousesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BookingsRepositoryTest extends KernelTestCase
{
    /** @var HousesRepository $housesRepository */
    private HousesRepository $housesRepository;
    /** @var BookingsRepository $bookingsRepository */
    private BookingsRepository $bookingsRepository;

    private EntityManagerInterface $entityManager;
    private string $housesCsvPath   = __DIR__ . '/../Resources/test_houses.csv';
    private string $bookingsCsvPath = __DIR__ . '/../Resources/test_bookings.csv';

    #[Override]
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $this->entityManager      = static::getContainer()->get('doctrine')->getManager();
        $this->bookingsRepository = $this->entityManager->getRepository(Booking::class);
        $this->housesRepository   = $this->entityManager->getRepository(House::class);

        $this->truncateEntities();

        $this->housesRepository->loadFromCsv($this->housesCsvPath);
        $this->bookingsRepository->loadFromCsv($this->bookingsCsvPath);
    }

    private function truncateEntities(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE booking RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE TABLE house RESTART IDENTITY CASCADE');
    }

    public function testFindAllBookings()
    {
        $bookings = $this->bookingsRepository->findAllBookings();

        $this->assertCount(2, $bookings);
        $this->assertEquals('+1234567890', $bookings[0]->getPhoneNumber());
        $this->assertEquals('Test booking 1', $bookings[0]->getComment());
        $this->assertEquals('+1987654321', $bookings[1]->getPhoneNumber());
        $this->assertEquals('Test booking 2', $bookings[1]->getComment());
    }

    public function testFindBookingById()
    {
        $booking = $this->bookingsRepository->findBookingById(1);

        $this->assertNotNull($booking);
        $this->assertEquals(1, $booking->getId());
        $this->assertEquals('+1234567890', $booking->getPhoneNumber());
        $this->assertEquals('Test booking 1', $booking->getComment());
    }

    public function testAddBooking()
    {
        $house = $this->housesRepository->find(1);
        $this->assertNotNull($house);

        $newBooking = (new Booking())
            ->setPhoneNumber('+1122334455')
            ->setComment('New test booking')
            ->setHouse($house);

        $this->bookingsRepository->addBooking($newBooking);

        $bookings = $this->bookingsRepository->findAllBookings();
        $this->assertCount(3, $bookings);
        $this->assertEquals('+1122334455', $bookings[2]->getPhoneNumber());
        $this->assertEquals('New test booking', $bookings[2]->getComment());
    }

    public function testUpdateBooking()
    {
        $booking = $this->bookingsRepository->findBookingById(1);
        $this->assertNotNull($booking);

        $booking->setPhoneNumber('+9988776655');
        $booking->setComment('Updated booking comment');
        $this->bookingsRepository->updateBooking($booking);

        $updatedBooking = $this->bookingsRepository->findBookingById(1);
        $this->assertEquals('+9988776655', $updatedBooking->getPhoneNumber());
        $this->assertEquals('Updated booking comment', $updatedBooking->getComment());
    }

    public function testDeleteBooking()
    {
        $this->bookingsRepository->deleteBookingById(1);

        $bookings = $this->bookingsRepository->findAllBookings();
        $this->assertCount(1, $bookings);

        $deletedBooking = $this->bookingsRepository->findBookingById(1);
        $this->assertNull($deletedBooking);
    }
}
