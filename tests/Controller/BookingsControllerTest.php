<?php
namespace App\Tests\Controller;

use App\Constant\BookingsMessages;
use App\Constant\HousesMessages;
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
    private static HousesRepository $housesRepository;
    /** @var BookingsRepository $bookingsRepository */
    private static BookingsRepository $bookingsRepository;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    // Test Data Paths
    private const HOUSES_CSV_PATH   = __DIR__ . '/../Resources/test_houses.csv';
    private const BOOKINGS_CSV_PATH = __DIR__ . '/../Resources/test_bookings.csv';

    // API Endpoints
    private const API_BOOKINGS    = '/api/v1/bookings/';
    private const API_BOOKINGS_ID = '/api/v1/bookings/%d';

    public static function setUpBeforeClass(): void
    {
        self::initializeDatabase();
    }

    protected static function initializeDatabase(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();
        self::assertSame('test', $kernel->getEnvironment());

        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $connection = $entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE booking RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE TABLE house RESTART IDENTITY CASCADE');

        self::$housesRepository = $entityManager->getRepository(House::class);
        self::$housesRepository->loadFromCsv(self::HOUSES_CSV_PATH);

        self::$bookingsRepository = $entityManager->getRepository(Booking::class);
        self::$bookingsRepository->loadFromCsv(self::BOOKINGS_CSV_PATH);
    }

    protected function setUp(): void
    {
        $this->client        = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        self::$housesRepository   = $this->entityManager->getRepository(House::class);
        self::$bookingsRepository = $this->entityManager->getRepository(Booking::class);
    }

    private function assertResponse(
        Response $response,
        int $expectedStatusCode,
        ?array $expectedContent = null
    ): void {
        $this->assertEquals(
            $expectedStatusCode,
            $response->getStatusCode()
        );
        $this->assertJson($response->getContent());

        if ($expectedContent) {
            $this->assertEquals(
                json_encode($expectedContent),
                $response->getContent()
            );
        }
    }

    private function assertBookingEquals(
        array $expected,
        array $actual,
    ): void {
        $fields = self::$bookingsRepository->getFields();

        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $expected);
            $this->assertArrayHasKey($field, $actual);
        }

        foreach ($fields as $field) {
            $this->assertEquals(
                $expected[$field],
                $actual[$field]
            );
        }
    }

    /*
     * Scenario: Listing all bookings
     * Given there are bookings in the system
     * When I request the list of bookings
     * Then I should receive a list of all bookings with status 200
     */
    public function testListBookings(): void
    {
        $expectedBookings = array_map(
            fn($booking) => $booking->toArray(),
            self::$bookingsRepository->findAllBookings()
        );

        $this->client->request(
            method: 'GET',
            uri: self::API_BOOKINGS
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: $expectedBookings
        );
    }

    /*
     * Scenario: Adding a new booking successfully
     * Given there is a house available for booking
     * When I create a new booking for the house
     * Then the booking should be created with status 201
     * And the house should be marked as unavailable
     */
    public function testAddBookingSuccess(): void
    {
        $expectedBookingId = 3;
        $newBooking        = (new Booking())
            ->setId($expectedBookingId)
            ->setPhoneNumber('+1234567890')
            ->setHouse(
                (new House())
                    ->setId(3)
            )
            ->setComment('Test booking 3')
            ->toArray();

        $this->assertTrue(
            self::$housesRepository
                ->findHouseById(
                    $newBooking['house_id']
                )
                ->isAvailable()
        );

        $this->client->request(
            method: 'POST',
            uri: self::API_BOOKINGS,
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($newBooking)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_CREATED,
            expectedContent: BookingsMessages::created()
        );

        $this->assertNotNull(
            self::$bookingsRepository
                ->findBookingById($expectedBookingId)
        );
        $this->assertBookingEquals(
            expected: $newBooking,
            actual: self::$bookingsRepository
                ->findBookingById($expectedBookingId)
                ->toArray()
        );

        $this->assertFalse(
            self::$housesRepository
                ->findHouseById(
                    $newBooking['house_id']
                )
                ->isAvailable()
        );
    }

    /**
     * Scenario: Adding a booking for a non-existent house
     * Given there is no house with the non-existent ID
     * When I create a new booking for the house
     * Then I should receive an error with status 404
     */
    public function testAddBookingHouseNotFound(): void
    {
        $expectedBookingId = 4;
        $expectedBooking   = (new Booking())
            ->setId($expectedBookingId)
            ->setPhoneNumber('+1234567890')
            ->setHouse(
                (new House())
                    ->setId(999)
            )
            ->setComment('Test booking 4')
            ->toArray();

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($expectedBookingId)
        );

        $this->client->request(
            method: 'POST',
            uri: self::API_BOOKINGS,
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($expectedBooking)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_NOT_FOUND,
            expectedContent: HousesMessages::notFound()
        );

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($expectedBookingId)
        );
    }

    /**
     * Scenario: Adding a booking for an unavailable house
     * Given the house is already booked
     * When I create a new booking for the house
     * Then I should receive an error with status 400
     */
    public function testAddBookingHouseNotAvailable(): void
    {
        $expectedBookingId = 4;
        $expectedBooking   = (new Booking())
            ->setId($expectedBookingId)
            ->setPhoneNumber('+1234567890')
            ->setHouse(
                (new House())
                    ->setId(1)
            )
            ->setComment('Test booking 4')
            ->toArray();

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($expectedBookingId)
        );

        $this->client->request(
            method: 'POST',
            uri: self::API_BOOKINGS,
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($expectedBooking)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_BAD_REQUEST,
            expectedContent: HousesMessages::notAvailable()
        );

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($expectedBookingId)
        );
    }

    /**
     * Scenario: Adding a booking with invalid data
     * Given the booking data is invalid
     * When I create a new booking with invalid data
     * Then I should receive an error with status 400
     */
    public function testAddBookingInvalidData(): void
    {
        $expectedBookingId = 4;
        $expectedMessage   = BookingsMessages::buildMessage(
            'Validation failed',
            [
                [
                    'field'   => 'phone_number',
                    'message' => 'This value should not be null.',
                ],
            ]
        );
        $booking = (new Booking())
            ->setId($expectedBookingId)
            ->setHouse(
                (new House())
                    ->setId(1)
            )
            ->setComment('Test booking 4')
            ->toArray();

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($expectedBookingId)
        );

        $this->client->request(
            method: 'POST',
            uri: self::API_BOOKINGS,
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($booking)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_BAD_REQUEST,
            expectedContent: $expectedMessage
        );

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($expectedBookingId)
        );
    }

    /**
     * Scenario: Getting a booking by ID
     * Given there is a booking with the specified ID
     * When I request the booking by ID
     * Then I should receive the booking details with status 200
     */
    public function testGetBookingById(): void
    {
        $bookingId       = 1;
        $expectedBooking = self::$bookingsRepository->findBookingById($bookingId);

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::API_BOOKINGS_ID, $bookingId)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: $expectedBooking->toArray()
        );
    }

    /**
     * Scenario: Getting a non-existent booking by ID
     * Given there is no booking with the specified ID
     * When I request the booking by ID
     * Then I should receive an error with status 404
     */
    public function testGetBookingByIdNotFound(): void
    {
        $bookingId = 999;

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($bookingId)
        );

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::API_BOOKINGS_ID, $bookingId)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_NOT_FOUND,
            expectedContent: BookingsMessages::notFound()
        );

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($bookingId)
        );
    }

    /**
     * Scenario: Replacing a booking successfully
     * Given there is a booking with the specified ID
     * When I replace the booking with a new house and comment
     * Then the booking should be replaced with status 200
     * And the old booking should be marked as available
     * And the new booking should be marked as unavailable
     * And the booking comment should be updated
     */
    public function testReplaceBookingSuccess(): void
    {
        $bookingId  = 1;
        $oldBooking = self::$bookingsRepository->findBookingById($bookingId)->toArray();
        $newBooking = (new Booking())
            ->setId($bookingId)
            ->setPhoneNumber('+1234567890')
            ->setHouse(
                (new House())
                    ->setId(4)
            )
            ->setComment('Replaced booking')
            ->toArray();

        $this->assertNotNull($oldBooking);

        $this->assertFalse(
            self::$housesRepository
                ->findHouseById($oldBooking['house_id'])
                ->isAvailable()
        );
        $this->assertTrue(
            self::$housesRepository
                ->findHouseById($newBooking['house_id'])
                ->isAvailable()
        );
        $this->assertEquals(
            $oldBooking['comment'],
            self::$bookingsRepository
                ->findBookingById($bookingId)
                ->getComment()
        );

        $this->client->request(
            method: 'PUT',
            uri: sprintf(self::API_BOOKINGS_ID, $bookingId),
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($newBooking)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: BookingsMessages::replaced()
        );

        $this->assertTrue(
            self::$housesRepository
                ->findHouseById($oldBooking['house_id'])
                ->isAvailable()
        );
        $this->assertFalse(
            self::$housesRepository
                ->findHouseById($newBooking['house_id'])
                ->isAvailable()
        );
        $this->assertEquals(
            $newBooking['comment'],
            self::$bookingsRepository
                ->findBookingById($bookingId)
                ->getComment()
        );
    }

    /**
     * Scenario: Replacing a non-existent booking
     * Given there is no booking with the specified ID
     * When I replace the booking with new data
     * Then I should receive an error with status 404
     */
    public function testReplaceBookingNotFound(): void
    {
        $bookingId = 999;

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($bookingId)
        );

        $this->client->request(
            method: 'PUT',
            uri: sprintf(self::API_BOOKINGS_ID, $bookingId),
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([])
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_NOT_FOUND,
            expectedContent: BookingsMessages::notFound()
        );

        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($bookingId)
        );
    }

    /**
     * Scenario: Updating a booking successfully
     * Given there is an existing booking
     * When I update the booking with updated comment
     * Then the booking should be updated with status 200
     * And the booking comment should be updated
     */
    public function testUpdateBookingSuccess(): void
    {
        $bookingId   = 1;
        $updatedData = [
            'comment' => 'Updated booking comment',
        ];

        $this->assertNotEquals(
            $updatedData['comment'],
            self::$bookingsRepository
                ->findBookingById($bookingId)
                ->getComment()
        );

        $this->client->request(
            method: 'PATCH',
            uri: sprintf(self::API_BOOKINGS_ID, $bookingId),
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($updatedData)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: BookingsMessages::updated()
        );

        $this->assertEquals(
            $updatedData['comment'],
            self::$bookingsRepository
                ->findBookingById($bookingId)
                ->getComment()
        );
    }

    /**
     * Scenario: Deleting a booking successfully
     * Given there is an existing booking
     * When I delete the booking
     * Then the booking should be deleted with status 200
     * And the house should be marked as available
     */
    public function testDeleteBookingSuccess(): void
    {
        $bookingId     = 1;
        $bookedHouseId = self::$bookingsRepository
            ->findBookingById($bookingId)
            ->getHouse()
            ->getId();

        $this->assertFalse(
            self::$housesRepository
                ->findHouseById($bookedHouseId)
                ->isAvailable()
        );
        $this->assertNotNull(
            self::$bookingsRepository
                ->findBookingById($bookingId)
        );

        $this->client->request(
            method: 'DELETE',
            uri: sprintf(self::API_BOOKINGS_ID, $bookingId)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: BookingsMessages::deleted()
        );

        $this->assertTrue(
            self::$housesRepository
                ->findHouseById($bookedHouseId)
                ->isAvailable()
        );
        $this->assertNull(
            self::$bookingsRepository
                ->findBookingById($bookingId)
        );
    }
}
