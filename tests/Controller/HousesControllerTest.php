<?php
namespace App\Tests\Controller;

use App\Constant\HousesMessages;
use App\Entity\House;
use App\Repository\HousesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HousesControllerTest extends WebTestCase
{
    /** @var HousesRepository $repository */
    private static HousesRepository $housesRepository;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    // Test Data Path
    private const HOUSES_CSV_PATH = __DIR__ . '/../Resources/test_houses.csv';

    // API Endpoints
    private const API_HOUSES    = '/api/v1/houses/';
    private const API_HOUSES_ID = '/api/v1/houses/%d';

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
        $connection->executeStatement('TRUNCATE TABLE house RESTART IDENTITY CASCADE');

        self::$housesRepository = $entityManager->getRepository(House::class);
        self::$housesRepository->loadFromCsv(self::HOUSES_CSV_PATH);
    }

    protected function setUp(): void
    {
        $this->client        = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        self::$housesRepository = $this->entityManager->getRepository(House::class);
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

    private function assertHouseEquals(
        array $expected,
        array $actual,
    ): void {
        $fields = self::$housesRepository->getFields();

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

    /**
     * Scenario: Listing all houses
     * Given there are houses in the system
     * When I request the list of houses
     * Then I should receive a list of all houses with status 200
     */
    public function testListHouses(): void
    {
        $expectedHouses = array_map(
            fn($house) => $house->toArray(),
            self::$housesRepository->findAllHouses()
        );

        $this->client->request(
            method: 'GET',
            uri: self::API_HOUSES
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: $expectedHouses
        );
    }

    /**
     * Scenario: Getting a house by ID
     * Given there is a house with the specified ID
     * When I request the house by ID
     * Then I should receive the house details with status 200
     * And the house details should match the expected data
     */
    public function testGetHouseById(): void
    {
        $houseId      = 1;
        $expectedData = self::$housesRepository->findHouseById($houseId);

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::API_HOUSES_ID, $houseId)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: $expectedData->toArray()
        );
    }

    /**
     * Scenario: Getting a non-existent house by ID
     * Given there is no house with the specified ID
     * When I request the house by ID
     * Then I should receive an error with status 404
     * And the house should not be found in the repository
     */
    public function testGetHouseByIdNotFound(): void
    {
        $houseId = 999;

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($houseId)
        );

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::API_HOUSES_ID, $houseId)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_NOT_FOUND,
            expectedContent: HousesMessages::notFound()
        );

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($houseId)
        );
    }

    /**
     * Scenario: Adding a new house successfully
     * Given I have valid house data
     * When I create a new house
     * Then the house should be created with status 201
     * And the house should be saved in the repository
     * And the house details should match the expected data
     */
    public function testAddHouseSuccess(): void
    {
        $expectedHouseId = 5;
        $expectedHouse   = (new House)
            ->setId($expectedHouseId)
            ->setIsAvailable(true)
            ->setBedroomsCount(12)
            ->setPricePerNight(6000)
            ->setHasAirConditioning(true)
            ->setHasWifi(true)
            ->setHasKitchen(true)
            ->setHasParking(true)
            ->setHasSeaView(false)
            ->toArray();

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($expectedHouseId)
        );

        $this->client->request(
            method: 'POST',
            uri: self::API_HOUSES,
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($expectedHouse)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_CREATED,
            expectedContent: HousesMessages::created()
        );

        $this->assertNotNull(
            self::$housesRepository
                ->findHouseById($expectedHouseId)
        );
        $this->assertHouseEquals(
            expected: $expectedHouse,
            actual: self::$housesRepository
                ->findHouseById($expectedHouseId)
                ->toArray()
        );
    }

    /**
     * Scenario: Adding a house with validation errors
     * Given I have invalid house data
     * When I create a new house
     * Then I should receive validation errors with status 400
     * And the house should not be saved in the repository
     */
    public function testAddHouseValidationError(): void
    {
        $expectedHouseId = 6;
        $expectedMessage = HousesMessages::buildMessage(
            'Validation failed',
            [
                [
                    'field'   => 'bedrooms_count',
                    'message' => 'This value should be between 1 and 20.',
                ],
            ]
        );
        $house = (new House)
            ->setId($expectedHouseId)
            ->setBedroomsCount(21)
            ->setPricePerNight(6000)
            ->setHasAirConditioning(true)
            ->setHasWifi(true)
            ->setHasKitchen(true)
            ->setHasParking(true)
            ->setHasSeaView(false)
            ->toArray();

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($expectedHouseId)
        );

        $this->client->request(
            method: 'POST',
            uri: self::API_HOUSES,
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($house)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_BAD_REQUEST,
            expectedContent: $expectedMessage
        );

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($expectedHouseId)
        );
    }

    /**
     * Scenario: Replacing a house successfully
     * Given there is an existing house
     * When I replace the house with new data
     * Then the house should be replaced with status 200
     * And the house details should match the new data
     */
    public function testReplaceHouseSuccess(): void
    {
        $houseId  = 1;
        $newHouse = (new House)
            ->setId($houseId)
            ->setIsAvailable(true)
            ->setBedroomsCount(12)
            ->setPricePerNight(7000)
            ->setHasAirConditioning(true)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(true)
            ->setHasSeaView(false)
            ->toArray();

        $actualHouse = self::$housesRepository->findHouseById($houseId);
        $this->assertNotNull($actualHouse);
        $this->assertNotEquals(
            $newHouse['bedrooms_count'],
            $actualHouse->getBedroomsCount()
        );
        $this->assertNotEquals(
            $newHouse['is_available'],
            $actualHouse->isAvailable()
        );
        $this->assertNotEquals(
            $newHouse['price_per_night'],
            $actualHouse->getPricePerNight()
        );

        $this->client->request(
            method: 'PUT',
            uri: sprintf(self::API_HOUSES_ID, $houseId),
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($newHouse)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: HousesMessages::replaced()
        );

        $this->assertNotNull(
            self::$housesRepository
                ->findHouseById($houseId)
        );
        $this->assertHouseEquals(
            expected: $newHouse,
            actual: self::$housesRepository
                ->findHouseById($houseId)
                ->toArray(),
        );
    }

    /**
     * Scenario: Replacing a non-existent house
     * Given there is no house with the specified ID
     * When I replace the house with new data
     * Then I should receive an error with status 404
     * And the house should not be created in the repository
     */
    public function testReplaceHouseNotFound(): void
    {
        $houseId = 999;

        $this->client->request(
            method: 'PUT',
            uri: sprintf(self::API_HOUSES_ID, $houseId),
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([])
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_NOT_FOUND,
            expectedContent: HousesMessages::notFound()
        );
    }

    /**
     * Scenario: Updating a house successfully
     * Given there is an existing house
     * When I update the house with new data
     * Then the house should be updated with status 200
     * And the house details should match the new data
     */
    public function testUpdateHouseSuccess(): void
    {
        $houseId  = 2;
        $newHouse = [
            'is_available'   => false,
            'bedrooms_count' => 19,
        ];

        $houseBeforeUpd = self::$housesRepository->findHouseById($houseId);
        $this->assertNotNull($houseBeforeUpd);
        $this->assertNotEquals(
            $newHouse['bedrooms_count'],
            $houseBeforeUpd->getBedroomsCount()
        );

        $this->client->request(
            method: 'PATCH',
            uri: sprintf(self::API_HOUSES_ID, $houseId),
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($newHouse)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: HousesMessages::updated()
        );

        $houseAfterUpd = self::$housesRepository->findHouseById($houseId);
        $this->assertNotNull($houseAfterUpd);
        $this->assertEquals(
            $newHouse['bedrooms_count'],
            $houseAfterUpd->getBedroomsCount()
        );
    }

    /**
     * Scenario: Updating a non-existent house
     * Given there is no house with the specified ID
     * When I update the house with new data
     * Then I should receive an error with status 404
     * And the house should not be created in the repository
     */
    public function testUpdateHouseNotFound(): void
    {
        $houseId = 999;

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($houseId)
        );

        $this->client->request(
            method: 'PATCH',
            uri: sprintf(self::API_HOUSES_ID, $houseId),
            parameters: [],
            files: [],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([])
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_NOT_FOUND,
            expectedContent: HousesMessages::notFound()
        );

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($houseId)
        );
    }

    /**
     * Scenario: Deleting a house successfully
     * Given there is an existing house
     * When I delete the house
     * Then the house should be deleted with status 200
     * And the house should not be found in the repository
     */
    public function testDeleteHouseSuccess(): void
    {
        $houseId = 1;

        $this->assertNotNull(
            self::$housesRepository
                ->findHouseById($houseId)
        );

        $this->client->request(
            method: 'DELETE',
            uri: sprintf(self::API_HOUSES_ID, $houseId)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_OK,
            expectedContent: HousesMessages::deleted()
        );

        $this->assertNull(
            self::$housesRepository
                ->findHouseById($houseId)
        );
    }

    /**
     * Scenario: Deleting a unavailable house
     * Given there is a house that is booked
     * When I try to delete the house
     * Then I should receive an error with status 400
     * And the house should still be available in the repository
     */
    public function testDeleteHouseBooked(): void
    {
        $houseId = 2;

        $this->assertFalse(
            self::$housesRepository
                ->findHouseById($houseId)
                ->isAvailable()
        );

        $this->client->request(
            method: 'DELETE',
            uri: sprintf(self::API_HOUSES_ID, $houseId)
        );
        $this->assertResponse(
            response: $this->client->getResponse(),
            expectedStatusCode: Response::HTTP_BAD_REQUEST,
            expectedContent: HousesMessages::booked()
        );

        $this->assertFalse(
            self::$housesRepository
                ->findHouseById($houseId)
                ->isAvailable()
        );
    }
}
