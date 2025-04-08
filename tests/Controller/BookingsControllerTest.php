<?php
namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Repository\BookingsRepository;
use App\Repository\HousesRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookingsControllerTest extends WebTestCase
{
    private $client;

    private $validator;
    private $serializer;

    private static $housesRepository;
    private static $bookingsRepository;

    public static function setUpBeforeClass(): void
    {
        self::initializeRepositories();
    }

    protected static function initializeRepositories()
    {
        copy(__DIR__ . '/../Resources/test_bookings.csv', __DIR__ . '/../Resources/~$test_bookings.csv');
        copy(__DIR__ . '/../Resources/test_houses.csv', __DIR__ . '/../Resources/~$test_houses.csv');

        self::$bookingsRepository = new BookingsRepository(__DIR__ . '/../Resources/~$test_bookings.csv');
        self::$housesRepository   = new HousesRepository(__DIR__ . '/../Resources/~$test_houses.csv');
    }

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator  = $this->createMock(ValidatorInterface::class);

        $this->client = static::createClient();
        $container    = ($this->client->getContainer());
        $container->set(BookingsRepository::class, self::$bookingsRepository);
        $container->set(HousesRepository::class, self::$housesRepository);
        $container->set(SerializerInterface::class, $this->serializer);
        $container->set(ValidatorInterface::class, $this->validator);
    }

    public function testListBookings()
    {
        $this->client->request('GET', '/api/v1/bookings/');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expectedData = self::$bookingsRepository->findAllBookings();
        $this->assertEquals(
            json_decode(json_encode($expectedData)),
            json_decode($response->getContent())
        );
    }

    public function testAddBookingSuccess()
    {
        $booking = (new Booking())
            ->setHouseId(3)
            ->setPhoneNumber('+1234567890')
            ->setComment('Test booking 3');

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($booking);

        $this->client->request(
            'POST',
            '/api/v1/bookings/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($booking)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'Booking created!']),
            $response->getContent()
        );

        $house = self::$housesRepository->findHouseById(3);
        $this->assertFalse($house->isAvailable());
    }

    public function testAddBookingHouseNotFound()
    {
        $booking = (new Booking())
            ->setHouseId(999)
            ->setPhoneNumber('+1234567890');

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($booking);

        $this->client->request(
            'POST',
            '/api/v1/bookings/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($booking)
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
        $booking = (new Booking())
            ->setHouseId(1)
            ->setPhoneNumber('1234567890');

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($booking);

        $this->client->request(
            'POST',
            '/api/v1/bookings/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($booking)
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

        $expectedData = self::$bookingsRepository->findBookingById(1);
        $this->assertEquals(
            json_decode(json_encode($expectedData)),
            json_decode($response->getContent())
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
        $newBooking = (new Booking())
            ->setId(3)
            ->setHouseId(4)
            ->setPhoneNumber('1234567890')
            ->setComment('Replaced booking 2');

        $houseBefore = self::$housesRepository->findHouseById(4);
        $this->assertTrue($houseBefore->isAvailable());

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($newBooking);

        $this->client->request(
            'PUT',
            '/api/v1/bookings/2',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($newBooking)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'Booking replaced!']),
            $response->getContent()
        );

        $houseAfter = self::$housesRepository->findHouseById(4);
        $this->assertFalse($houseAfter->isAvailable());
    }

    public function testReplaceBookingNotFound()
    {
        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn(new Booking());

        $this->client->request(
            'PUT',
            '/api/v1/bookings/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'Booking not found']),
            $response->getContent()
        );
    }

    public function testUpdateBookingSuccess()
    {
        $updatedData = [
            'house_id' => 2,
            'comment'  => 'Updated booking 1',
        ];

        $houseBefore = self::$housesRepository->findHouseById(2);
        $this->assertTrue($houseBefore->isAvailable());

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn(
                (new Booking())
                    ->setHouseId(2)
                    ->setComment('Updated booking 1')
            );

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

        $houseAfter = self::$housesRepository->findHouseById(2);
        $this->assertFalse($houseAfter->isAvailable());
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

        $house = self::$housesRepository->findHouseById(2);
        $this->assertTrue($house->isAvailable());
    }
}
