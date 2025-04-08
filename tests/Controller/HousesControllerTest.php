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

    private static $housesRepository;

    public static function setUpBeforeClass(): void
    {
        self::initializeRepositories();
    }

    protected static function initializeRepositories()
    {
        copy(__DIR__ . '/../Resources/test_houses.csv', __DIR__ . '/../Resources/~$test_houses.csv');

        self::$housesRepository = new HousesRepository(__DIR__ . '/../Resources/~$test_houses.csv');
    }

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator  = $this->createMock(ValidatorInterface::class);

        $this->client = static::createClient();
        $container    = ($this->client->getContainer());
        $container->set(HousesRepository::class, self::$housesRepository);
        $container->set(SerializerInterface::class, $this->serializer);
        $container->set(ValidatorInterface::class, $this->validator);
    }

    public function testListHouses()
    {
        $this->client->request('GET', '/api/v1/houses/');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expectedData = self::$housesRepository->findAllHouses();
        $this->assertEquals(
            json_decode(json_encode($expectedData)),
            json_decode($response->getContent())
        );
    }

    public function testGetHouseById()
    {
        $this->client->request('GET', '/api/v1/houses/1');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $expectedData = self::$housesRepository->findHouseById(1);
        $this->assertEquals(
            json_decode(json_encode($expectedData)),
            json_decode($response->getContent())
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

        $addedHouse = self::$housesRepository->findHouseById(5);
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

        $houseBeforeReplacing = self::$housesRepository->findHouseById(1);
        $this->assertFalse($houseBeforeReplacing->isAvailable());

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

        $houseAfterReplacing = self::$housesRepository->findHouseById(1);
        $this->assertTrue($houseAfterReplacing->isAvailable());
    }

    public function testReplaceHouseNotFound()
    {
        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn(new House());

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

        $houseBeforeReplacing = self::$housesRepository->findHouseById(2);
        $this->assertEquals(3, $houseBeforeReplacing->getBedroomsCount());

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn(
                (new House())
                    ->setIsAvailable(false)
                    ->setBedroomsCount(19)
            );

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

        $houseAfterReplacing = self::$housesRepository->findHouseById(2);
        $this->assertEquals(19, $houseAfterReplacing->getBedroomsCount());
    }

    public function testUpdateHouseNotFound()
    {
        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn(new House());

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

        $deletedHouse = self::$housesRepository->findHouseById(1);
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
