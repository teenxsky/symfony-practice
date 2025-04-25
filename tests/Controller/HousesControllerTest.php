<?php

declare (strict_types=1);

namespace App\Tests\Controller;

use App\Entity\House;
use App\Repository\HousesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class HousesControllerTest extends WebTestCase
{
    /** @var HousesRepository $repository */
    private HousesRepository $housesRepository;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $housesCsvPath = __DIR__ . '/../Resources/test_houses.csv';

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager    = static::getContainer()->get('doctrine')->getManager();
        $this->housesRepository = $this->entityManager->getRepository(House::class);

        $this->truncateEntities();
        $this->housesRepository->loadFromCsv($this->housesCsvPath);
    }

    private function truncateEntities(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE house RESTART IDENTITY CASCADE');
    }

    public function testListHouses()
    {
        $this->client->request('GET', '/api/v1/houses/');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expectedData = array_map(
            fn ($house) => $house->toArray(),
            $this->housesRepository->findAllHouses()
        );

        $this->assertEquals(
            json_encode($expectedData),
            $response->getContent()
        );
    }

    public function testGetHouseById()
    {
        $this->client->request('GET', '/api/v1/houses/1');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expectedData = $this->housesRepository->findHouseById(1)->toArray();

        $decodedData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $decodedData);
        $this->assertArrayHasKey('bedroomsCount', $decodedData);
        $this->assertArrayHasKey('pricePerNight', $decodedData);
        $this->assertArrayHasKey('hasAirConditioning', $decodedData);
        $this->assertArrayHasKey('hasWifi', $decodedData);
        $this->assertArrayHasKey('hasKitchen', $decodedData);
        $this->assertArrayHasKey('hasParking', $decodedData);
        $this->assertArrayHasKey('hasSeaView', $decodedData);
        $this->assertArrayHasKey('isAvailable', $decodedData);

        $this->assertEquals(
            $expectedData,
            $decodedData
        );
    }

    public function testGetHouseByIdNotFound()
    {
        $this->client->request('GET', '/api/v1/houses/999');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'House not found']),
            $response->getContent()
        );
    }

    public function testAddHouseSuccess()
    {
        $house = [
            'bedrooms_count'       => 12,
            'price_per_night'      => 6000,
            'has_air_conditioning' => true,
            'has_wifi'             => true,
            'has_kitchen'          => true,
            'has_parking'          => true,
            'has_sea_view'         => false,
        ];

        $this->client->request(
            'POST',
            '/api/v1/houses/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($house)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(json_encode(['status' => 'House created!']), $response->getContent());

        $addedHouse = $this->housesRepository->findHouseById(5);
        $this->assertNotNull($addedHouse);
    }

    public function testAddHouseValidationError()
    {
        $house = [
            'bedrooms_count'       => 21,
            'price_per_night'      => 6000,
            'has_air_conditioning' => true,
            'has_wifi'             => true,
            'has_kitchen'          => true,
            'has_parking'          => true,
            'has_sea_view'         => true,
        ];

        $this->client->request(
            'POST',
            '/api/v1/houses/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($house),
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode([
                'status' => 'Validation failed',
                'errors' => [
                    [
                        'field'   => 'bedroomsCount',
                        'message' => 'This value should be between 1 and 20.',
                    ],
                ],
            ]),
            $response->getContent()
        );
    }

    public function testReplaceHouseSuccess()
    {
        $house = [
            'id'                   => 1,
            'is_available'         => true,
            'bedrooms_count'       => 12,
            'price_per_night'      => 7000,
            'has_air_conditioning' => true,
            'has_wifi'             => false,
            'has_kitchen'          => false,
            'has_parking'          => true,
            'has_sea_view'         => false,
        ];

        $houseBeforeReplacing = $this->housesRepository->findHouseById(1);
        $this->assertFalse($houseBeforeReplacing->isAvailable());

        $this->client->request(
            'PUT',
            '/api/v1/houses/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($house)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'House replaced!']),
            $response->getContent()
        );

        $houseAfterReplacing = $this->housesRepository->findHouseById(1);
        $this->assertTrue($houseAfterReplacing->isAvailable());
    }

    public function testReplaceHouseNotFound()
    {
        $this->client->request(
            'PUT',
            '/api/v1/houses/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'House not found']),
            $response->getContent()
        );
    }

    public function testUpdateHouseSuccess()
    {
        $updatedData = [
            'is_available'   => false,
            'bedrooms_count' => 19,
        ];

        $houseBeforeReplacing = $this->housesRepository->findHouseById(2);
        $this->assertEquals(3, $houseBeforeReplacing->getBedroomsCount());

        $this->client->request(
            'PATCH',
            '/api/v1/houses/2',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'House updated!']),
            $response->getContent()
        );

        $houseAfterReplacing = $this->housesRepository->findHouseById(2);
        $this->assertEquals(19, $houseAfterReplacing->getBedroomsCount());
    }

    public function testUpdateHouseNotFound()
    {
        $this->client->request(
            'PATCH',
            '/api/v1/houses/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'House not found']),
            $response->getContent()
        );
    }

    public function testDeleteHouseSuccess()
    {
        $this->client->request('DELETE', '/api/v1/houses/3');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'House deleted!']),
            $response->getContent()
        );

        $deletedHouse = $this->housesRepository->findHouseById(3);
        $this->assertNull($deletedHouse);
    }

    public function testDeleteHouseBooked()
    {
        $this->client->request('DELETE', '/api/v1/houses/2');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['status' => 'House is booked']),
            $response->getContent()
        );
    }
}
