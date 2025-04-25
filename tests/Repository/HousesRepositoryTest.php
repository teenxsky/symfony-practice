<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\House;
use App\Repository\HousesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HousesRepositoryTest extends KernelTestCase
{
    /** @var HousesRepository $housesRepository */
    private EntityRepository $housesRepository;

    private EntityManagerInterface $entityManager;
    private string $housesCsvPath = __DIR__ . '/../Resources/test_houses.csv';

    #[Override]
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

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

    public function testFindAllHouses()
    {
        $houses = $this->housesRepository->findAllHouses();

        $this->assertCount(4, $houses);
        $this->assertEquals(2, $houses[0]->getBedroomsCount());
        $this->assertEquals(500, $houses[0]->getPricePerNight());
        $this->assertEquals(3, $houses[1]->getBedroomsCount());
        $this->assertEquals(700, $houses[1]->getPricePerNight());
    }

    public function testFindHouseById()
    {
        $house = $this->housesRepository->findHouseById(1);

        $this->assertNotNull($house);
        $this->assertEquals(1, $house->getId());
        $this->assertEquals(2, $house->getBedroomsCount());
        $this->assertEquals(500, $house->getPricePerNight());
    }

    public function testAddHouse()
    {
        $newHouse = (new House())
            ->setIsAvailable(true)
            ->setBedroomsCount(5)
            ->setPricePerNight(1200)
            ->setHasAirConditioning(true)
            ->setHasWifi(true)
            ->setHasKitchen(true)
            ->setHasParking(true)
            ->setHasSeaView(false);

        $this->housesRepository->addHouse($newHouse);

        $houses = $this->housesRepository->findAllHouses();
        $this->assertCount(5, $houses);
        $this->assertEquals(5, $houses[4]->getBedroomsCount());
        $this->assertEquals(1200, $houses[4]->getPricePerNight());
    }

    public function testUpdateHouse()
    {
        $house = $this->housesRepository->findHouseById(1);
        $this->assertNotNull($house);

        $house->setBedroomsCount(10);
        $house->setPricePerNight(2000);
        $this->housesRepository->updateHouse($house);

        $updatedHouse = $this->housesRepository->findHouseById(1);
        $this->assertEquals(10, $updatedHouse->getBedroomsCount());
        $this->assertEquals(2000, $updatedHouse->getPricePerNight());
    }

    public function testDeleteHouse()
    {
        $this->housesRepository->deleteHouse(1);

        $houses = $this->housesRepository->findAllHouses();
        $this->assertCount(3, $houses);

        $deletedHouse = $this->housesRepository->findHouseById(1);
        $this->assertNull($deletedHouse);
    }
}
