<?php
namespace App\Tests\Repository;

use App\Entity\House;
use App\Repository\HousesRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HousesRepositoryTest extends TestCase
{
    private string $filePath = __DIR__ . '/../Resources/~$test_houses.csv';

    /** @var House[] */
    private array $houses = [];
    /** @var HousesRepository $housesRepository */
    private HousesRepository $housesRepository;

    protected function setUp(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        $this->houses[] = (new House())
            ->setId(1)
            ->setBedroomsCount(2)
            ->setPricePerNight(500)
            ->setHasAirConditioning(true)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(true)
            ->setHasSeaView(false);

        $this->houses[] = (new House())
            ->setId(2)
            ->setBedroomsCount(21)
            ->setPricePerNight(15000)
            ->setHasAirConditioning(false)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(false)
            ->setHasSeaView(false);

        $this->houses[] = (new House())
            ->setId(3)
            ->setBedroomsCount(20)
            ->setPricePerNight(100001)
            ->setHasAirConditioning(false)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(false)
            ->setHasSeaView(false);

        $this->housesRepository = new HousesRepository($this->filePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        $this->houses = [];
    }

    private function assertHousesEqual(House $expected, House $actual): void
    {
        $this->assertEquals(
            $expected->getId(),
            $actual->getId());
        $this->assertEquals(
            $expected->getBedroomsCount(),
            $actual->getBedroomsCount()
        );
        $this->assertEquals(
            $expected->getPricePerNight(),
            $actual->getPricePerNight()
        );
        $this->assertEquals(
            $expected->hasAirConditioning(),
            $actual->hasAirConditioning()
        );
        $this->assertEquals(
            $expected->hasWifi(),
            $actual->hasWifi()
        );
        $this->assertEquals(
            $expected->hasKitchen(),
            $actual->hasKitchen()
        );
        $this->assertEquals(
            $expected->hasParking(),
            $actual->hasParking()
        );
        $this->assertEquals(
            $expected->hasSeaView(),
            $actual->hasSeaView()
        );
        $this->assertEquals(
            $expected->isAvailable(),
            $actual->isAvailable()
        );
    }

    public function testAddHouse(): void
    {
        $houseId       = 1;
        $expectedHouse = $this->houses[$houseId - 1];

        $this->housesRepository->addHouse($expectedHouse);

        $this->assertCount(
            1,
            $this->housesRepository->findAllHouses()
        );

        $actualHouse = $this->housesRepository->findHouseById($houseId);

        $this->assertHousesEqual(
            expected: $expectedHouse,
            actual: $actualHouse
        );
    }

    public function testFindAllHouses(): void
    {
        $countBefore = 0;
        $countAfter  = count($this->houses);

        $this->assertCount(
            $countBefore,
            $this->housesRepository->findAllHouses()
        );

        foreach ($this->houses as $house) {
            $this->housesRepository->addHouse($house);
        }

        $houses = $this->housesRepository->findAllHouses();

        $this->assertCount($countAfter, $houses);
        for ($i = 0; $i < $countAfter; $i++) {
            $this->assertHousesEqual(
                expected: $this->houses[$i],
                actual: $houses[$i]
            );
        }
    }

    public function testFindHouseById(): void
    {
        $houseId       = 2;
        $expectedHouse = $this->houses[$houseId - 1];

        foreach ($this->houses as $house) {
            $this->housesRepository->addHouse($house);
        }

        $actualHouse = $this->housesRepository->findHouseById($houseId);

        $this->assertNotNull($actualHouse);
        $this->assertHousesEqual(
            expected: $expectedHouse,
            actual: $actualHouse
        );
    }

    public function testUpdateHouse(): void
    {
        $houseId = 2;

        foreach ($this->houses as $house) {
            $this->housesRepository->addHouse($house);
        }

        // Before updating house
        $expectedHouse = $this->houses[$houseId - 1];
        $actualHouse   = $this->housesRepository->findHouseById($houseId);

        $this->assertNotNull($actualHouse);
        $this->assertHousesEqual(
            expected: $expectedHouse,
            actual: $actualHouse
        );

        // After updating house
        $expectedHouse = $this->houses[$houseId - 1];
        $expectedHouse->setBedroomsCount(3);
        $expectedHouse->setPricePerNight(600);

        $this->housesRepository->updateHouse($expectedHouse);

        $actualHouse = $this->housesRepository->findHouseById($houseId);

        $this->assertNotNull($actualHouse);
        $this->assertHousesEqual(
            expected: $expectedHouse,
            actual: $actualHouse
        );
    }

    public function testDeleteHouse(): void
    {
        $houseId     = 2;
        $countBefore = count($this->houses);
        $countAfter  = count($this->houses) - 1;

        foreach ($this->houses as $house) {
            $this->housesRepository->addHouse($house);
        }

        $this->assertCount(
            $countBefore,
            $this->housesRepository->findAllHouses()
        );

        $this->housesRepository->deleteHouse($houseId);

        $this->assertCount(
            $countAfter,
            $this->housesRepository->findAllHouses()
        );
        $this->assertNull(
            $this->housesRepository->findHouseById($houseId)
        );
    }

    public function testSaveHousesPrivate(): void
    {
        $methodName = 'saveHouses';

        $reflection = new ReflectionClass($this->housesRepository);
        $method     = $reflection->getMethod($methodName);

        $this->assertTrue(
            method_exists($this->housesRepository, $methodName)
        );
        $this->assertTrue($method->isPrivate());
    }
}
