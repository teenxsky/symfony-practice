<?php
namespace App\Tests\Repository;

use App\Entity\House;
use App\Repository\HousesRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HousesRepositoryTest extends TestCase
{
    private $filePath = __DIR__ . '/../Resources/~$test_houses.csv';
    private $houses = [];

    protected function setUp(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
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
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        $this->houses = [];
    }

    public function testFindAllHouses()
    {
        $repository = new HousesRepository($this->filePath);

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
        $repository = new HousesRepository($this->filePath);

        foreach ($this->houses as $house) {
            $repository->addHouse($house);
        }

        $foundHouse1 = $repository->findHouseById(1);
        $this->assertNotNull($foundHouse1);
        $this->assertEquals(1, $foundHouse1->getId());
        $this->assertEquals(2, $foundHouse1->getBedroomsCount());
        $this->assertEquals(500, $foundHouse1->getPricePerNight());

        $foundHouse2 = $repository->findHouseById(2);
        $this->assertNotNull($foundHouse2);
        $this->assertEquals(2, $foundHouse2->getId());
        $this->assertEquals(21, $foundHouse2->getBedroomsCount());
        $this->assertEquals(15000, $foundHouse2->getPricePerNight());

        $foundHouse3 = $repository->findHouseById(3);
        $this->assertNotNull($foundHouse3);
        $this->assertEquals(3, $foundHouse3->getId());
        $this->assertEquals(20, $foundHouse3->getBedroomsCount());
        $this->assertEquals(100001, $foundHouse3->getPricePerNight());

        $notFoundHouse = $repository->findHouseById(999);
        $this->assertNull($notFoundHouse);
    }

    public function testAddHouse()
    {
        $repository = new HousesRepository($this->filePath);

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
        $repository = new HousesRepository($this->filePath);

        $house = $this->houses[0];
        $repository->addHouse($house);
        
        $uploadedHouse = $repository->findHouseById(1);
        $this->assertNotNull($uploadedHouse);
        $this->assertEquals(1, $uploadedHouse->getId());
        $this->assertEquals(2, $uploadedHouse->getBedroomsCount());
        $this->assertEquals(500, $uploadedHouse->getPricePerNight());

        $house->setBedroomsCount(3);
        $house->setPricePerNight(600);
        $repository->updateHouse($house);

        $updatedHouse = $repository->findHouseById(1);
        $this->assertNotNull($updatedHouse);
        $this->assertEquals(1, $updatedHouse->getId());
        $this->assertEquals(3, $updatedHouse->getBedroomsCount());
        $this->assertEquals(600, $updatedHouse->getPricePerNight());
    }

    public function testDeleteHouse()
    {
        $repository = new HousesRepository($this->filePath);

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
        $repository = new HousesRepository($this->filePath);

        $reflection = new ReflectionClass($repository);
        $method = $reflection->getMethod('saveHouses');
        $this->assertTrue($method->isPrivate());
    }
}
