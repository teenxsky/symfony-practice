<?php
namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Entity\House;
use App\Repository\BookingsRepository;
use App\Repository\HousesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookingsControllerTest extends WebTestCase
{
    /** @var HousesRepository $housesRepository */
    private HousesRepository $housesRepository;
    /** @var BookingsRepository $bookingsRepository */
    private BookingsRepository $bookingsRepository;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $housesCsvPath   = __DIR__ . '/../Resources/test_houses.csv';
    private string $bookingsCsvPath = __DIR__ . '/../Resources/test_bookings.csv';

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager      = $this->client->getContainer()->get('doctrine')->getManager();
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

    public function testListBookings()
    {
        $this->client->request('GET', '/api/v1/bookings/');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expectedData = array_map(
            fn($booking) => $booking->toArray(),
            $this->bookingsRepository->findAllBookings()
        );

        $this->assertEquals(
            json_encode($expectedData),
            $response->getContent()
        );
    }

    public function testAddBookingSuccess()
    {
        $bookingData = [
            'houseId'     => 3,
            'phoneNumber' => '+1234567890',
            'comment'     => 'Test booking 3',
        ];

        $this->client->request(
            'POST',
            '/api/v1/bookings/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'Booking created!']),
            $response->getContent()
        );

        $house = $this->housesRepository->findHouseById(3);
        $this->assertFalse($house->isAvailable());
    }

    public function testAddBookingHouseNotFound()
    {
        $bookingData = [
            'houseId'     => 999,
            'phoneNumber' => '+1234567890',
        ];

        $this->client->request(
            'POST',
            '/api/v1/bookings/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'House not found']),
            $response->getContent()
        );
    }

    public function testAddBookingHouseNotAvailable()
    {
        $bookingData = [
            'houseId'     => 1,
            'phoneNumber' => '+1234567890',
        ];

        $this->client->request(
            'POST',
            '/api/v1/bookings/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'House is not available']),
            $response->getContent()
        );
    }

    public function testGetBookingById()
    {
        $this->client->request('GET', '/api/v1/bookings/1');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expectedData = $this->bookingsRepository->findBookingById(1)->toArray();

        $this->assertEquals(
            json_encode($expectedData),
            $response->getContent()
        );
    }

    public function testGetBookingByIdNotFound()
    {
        $this->client->request('GET', '/api/v1/bookings/999');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'Booking not found']),
            $response->getContent()
        );
    }

    public function testReplaceBookingSuccess()
    {
        $bookingData = [
            'houseId'     => 3,
            'phoneNumber' => '+1234567890',
            'comment'     => 'Replaced booking',
        ];

        $houseBefore = $this->housesRepository->findHouseById(3);
        $this->assertTrue($houseBefore->isAvailable());

        $this->client->request(
            'PUT',
            '/api/v1/bookings/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'Booking replaced!']),
            $response->getContent()
        );

        $houseAfter = $this->housesRepository->findHouseById(3);
        $this->assertFalse($houseAfter->isAvailable());

        $updatedBooking = $this->bookingsRepository->findBookingById(1);
        $this->assertEquals(3, $updatedBooking->getHouse()->getId());
        $this->assertEquals('Replaced booking', $updatedBooking->getComment());
    }

    public function testReplaceBookingNotFound()
    {
        $bookingData = [
            'houseId'     => 3,
            'phoneNumber' => '+1234567890',
            'comment'     => 'Replaced booking',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/bookings/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'Booking not found']),
            $response->getContent()
        );
    }

    public function testUpdateBookingSuccess()
    {
        $updatedData = [
            'comment' => 'Updated booking comment',
        ];

        $this->client->request(
            'PATCH',
            '/api/v1/bookings/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'Booking updated!']),
            $response->getContent()
        );

        $updatedBooking = $this->bookingsRepository->findBookingById(1);
        $this->assertEquals('Updated booking comment', $updatedBooking->getComment());
    }

    public function testDeleteBookingSuccess()
    {
        $this->client->request('DELETE', '/api/v1/bookings/1');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'Booking deleted!']),
            $response->getContent()
        );

        $deletedBooking = $this->bookingsRepository->findBookingById(1);
        $this->assertNull($deletedBooking);

        $house = $this->housesRepository->findHouseById(1);
        $this->assertTrue($house->isAvailable());
    }
}
