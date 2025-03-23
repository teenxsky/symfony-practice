<?php
namespace App\Tests\Repository;

use App\Entity\House;
use App\Repository\HousesRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HousesRepositoryTest extends TestCase
{
    private $file_path = __DIR__ . '/../Resources/~$test_houses.csv';
    private $houses    = [];

    protected function setUp(): void
    {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }

        $this->houses[] = (new House())
            ->setBedroomsCount(2)
            ->setPricePerNight(500)
            ->setHasAirConditioning(true)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(true)
            ->setHasSeaView(false);

        $this->houses[] = (new House())
            ->setBedroomsCount(21)
            ->setPricePerNight(15000)
            ->setHasAirConditioning(false)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(false)
            ->setHasSeaView(false);

        $this->houses[] = (new House())
            ->setBedroomsCount(20)
            ->setPricePerNight(100001)
            ->setHasAirConditioning(false)
            ->setHasWifi(false)
            ->setHasKitchen(false)
            ->setHasParking(false)
            ->setHasSeaView(false);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }

        $this->houses = [];
    }

    public function testFindAllHouses()
    {
        $repository = new HousesRepository($this->file_path);

        $houses = $repository->findAllHouses();
        $this->assertCount(0, $houses);

        foreach ($this->houses as $house) {
            $repository->addHouse($house);
        }

        $houses = $repository->findAllHouses();
        $this->assertCount(3, $houses);
    }

    public function testFindHouseById()
    {
        $repository = new HousesRepository($this->file_path);

        foreach ($this->houses as $house) {
            $repository->addHouse($house);
        }

        $found_house1 = $repository->findHouseById(1);
        $this->assertNotNull($found_house1);
        $this->assertEquals(1, $found_house1->getId());
        $this->assertEquals(2, $found_house1->getBedroomsCount());
        $this->assertEquals(500, $found_house1->getPricePerNight());

        $found_house2 = $repository->findHouseById(2);
        $this->assertNotNull($found_house2);
        $this->assertEquals(2, $found_house2->getId());
        $this->assertEquals(21, $found_house2->getBedroomsCount());
        $this->assertEquals(15000, $found_house2->getPricePerNight());

        $found_house3 = $repository->findHouseById(3);
        $this->assertNotNull($found_house3);
        $this->assertEquals(3, $found_house3->getId());
        $this->assertEquals(20, $found_house3->getBedroomsCount());
        $this->assertEquals(100001, $found_house3->getPricePerNight());

        $not_found_house = $repository->findHouseById(999);
        $this->assertNull($not_found_house);
    }

    public function testAddHouse()
    {
        $repository = new HousesRepository($this->file_path);

        $repository->addHouse($this->houses[0]);

        $houses = $repository->findAllHouses();
        $this->assertCount(1, $houses);
        $this->assertEquals(1, $houses[0]->getId());
        $this->assertTrue($houses[0]->isAvailable());
        $this->assertEquals(2, $houses[0]->getBedroomsCount());
        $this->assertEquals(500, $houses[0]->getPricePerNight());
        $this->assertTrue($houses[0]->hasAirConditioning());
        $this->assertFalse($houses[0]->hasWifi());
        $this->assertFalse($houses[0]->hasKitchen());
        $this->assertTrue($houses[0]->hasParking());
        $this->assertFalse($houses[0]->hasSeaView());
    }

    public function testUpdateHouse()
    {
        $repository = new HousesRepository($this->file_path);

        $house = $this->houses[0];
        $repository->addHouse($house);
        
        $uploaded_house = $repository->findHouseById(1);
        $this->assertNotNull($uploaded_house);
        $this->assertEquals(1, $uploaded_house->getId());
        $this->assertEquals(2, $uploaded_house->getBedroomsCount());
        $this->assertEquals(500, $uploaded_house->getPricePerNight());

        $house->setBedroomsCount(3);
        $house->setPricePerNight(600);
        $repository->updateHouse($house);

        $updated_house = $repository->findHouseById(1);
        $this->assertNotNull($updated_house);
        $this->assertEquals(1, $updated_house->getId());
        $this->assertEquals(3, $updated_house->getBedroomsCount());
        $this->assertEquals(600, $updated_house->getPricePerNight());
    }

    public function testDeleteHouse()
    {
        $repository = new HousesRepository($this->file_path);

        foreach ($this->houses as $house) {
            $repository->addHouse($house);
        }

        $houses = $repository->findAllHouses();
        $this->assertCount(3, $houses);
        $this->assertEquals(1, $houses[0]->getId());
        $this->assertEquals(2, $houses[1]->getId());
        $this->assertEquals(3, $houses[2]->getId());

        $repository->deleteHouse(2);

        $houses = $repository->findAllHouses();
        $this->assertCount(2, $houses);
        $this->assertEquals(1, $houses[0]->getId());
        $this->assertNotEquals(2, $houses[1]->getId());
        $this->assertEquals(3, $houses[1]->getId());
    }

    public function testSaveHousesPrivate()
    {
        $repository = new HousesRepository($this->file_path);

        $reflection = new ReflectionClass($repository);
        $method     = $reflection->getMethod('saveHouses');
        $this->assertTrue($method->isPrivate());
    }
}
