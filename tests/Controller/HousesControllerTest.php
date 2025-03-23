<?php
namespace App\Tests\Controller;

use App\Entity\House;
use App\Repository\HousesRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HousesControllerTest extends WebTestCase
{
    private $client;

    private $validator;
    private $serializer;

    private static $houses_repository;

    public static function setUpBeforeClass(): void
    {
        self::initializeRepositories();
    }

    protected static function initializeRepositories()
    {
        copy(__DIR__ . '/../Resources/test_houses.csv', __DIR__ . '/../Resources/~$test_houses.csv');

        self::$houses_repository = new HousesRepository(__DIR__ . '/../Resources/~$test_houses.csv');
    }

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator  = $this->createMock(ValidatorInterface::class);

        $this->client = static::createClient();
        $container    = ($this->client->getContainer());
        $container->set(HousesRepository::class, self::$houses_repository);
        $container->set(SerializerInterface::class, $this->serializer);
        $container->set(ValidatorInterface::class, $this->validator);
    }

    public function testListHouses()
    {
        $this->client->request('GET', '/api/v1/houses/');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expected_data = self::$houses_repository->findAllHouses();
        $this->assertEquals(
            json_encode($expected_data),
            $response->getContent()
        );
    }

    public function testGetHouseById()
    {
        $this->client->request('GET', '/api/v1/houses/1');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expected_data = self::$houses_repository->findHouseById(1);
        $this->assertEquals(
            json_encode($expected_data),
            $response->getContent()
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

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn((new House())
                    ->setBedroomsCount(12)
                    ->setPricePerNight(6000)
                    ->setHasAirConditioning(true)
                    ->setHasWifi(true)
                    ->setHasKitchen(true)
                    ->setHasParking(true)
                    ->setHasSeaView(false));

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

        $addedHouse = self::$houses_repository->findHouseById(5);
        $this->assertNotNull($addedHouse);
    }

    public function testAddHouseValidationError()
    {
        $house = (new House())
            ->setBedroomsCount(21)
            ->setPricePerNight(6000)
            ->setHasAirConditioning(true)
            ->setHasWifi(true)
            ->setHasKitchen(true)
            ->setHasParking(true)
            ->setHasSeaView(true);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($house);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation(
                    'Invalid value',
                    null,
                    [],
                    null,
                    'bedroomsCount',
                    21
                ),
            ]));

        $this->client->request(
            'POST',
            '/api/v1/houses/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($house)
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
                        'message' => 'Invalid value',
                    ],
                ],
            ]),
            $response->getContent()
        );
    }

    public function testReplaceHouseSuccess()
    {
        $house = (new House())
            ->setId(1)
            ->setIsAvailable(true)
            ->setBedroomsCount(12)
            ->setPricePerNight(7000)
            ->setHasAirConditioning(true)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(true)
            ->setHasSeaView(false);

        $house_before_replacing = self::$houses_repository->findHouseById(1);
        $this->assertFalse($house_before_replacing->isAvailable());

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($house);

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

        $house_after_replacing = self::$houses_repository->findHouseById(1);
        $this->assertTrue($house_after_replacing->isAvailable());
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
        $updated_data = [
            'is_available'   => false,
            'bedrooms_count' => 19,
        ];

        $house_before_replacing = self::$houses_repository->findHouseById(2);
        $this->assertEquals(3, $house_before_replacing->getBedroomsCount());

        $this->serializer
            ->expects($this->once())
            ->method('deserialize') 
            ->willReturn((new House())
                    ->setIsAvailable(false)
                    ->setBedroomsCount(19));

        $this->client->request(
            'PATCH',
            '/api/v1/houses/2',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updated_data)
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'House updated!']),
            $response->getContent()
        );

        $house_after_replacing = self::$houses_repository->findHouseById(2);
        $this->assertEquals(19, $house_after_replacing->getBedroomsCount());
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
        $this->client->request('DELETE', '/api/v1/houses/1');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(
            json_encode(['status' => 'House deleted!']),
            $response->getContent()
        );

        $deleted_house = self::$houses_repository->findHouseById(1);
        $this->assertNull($deleted_house);
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
