<?php
namespace App\Repository;

use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HousesRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, House::class);
    }

    public function findAllHouses(): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findHouseById(int $id): ?House
    {
        return $this->find($id);
    }

    public function addHouse(House $house): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($house);
        $entityManager->flush();
    }

    public function updateHouse(House $updatedHouse): void
    {
        $entityManager = $this->getEntityManager();
        $house         = $this->find($updatedHouse->getId());
        if ($house) {
            ($house)
                ->setIsAvailable($updatedHouse->isAvailable())
                ->setBedroomsCount($updatedHouse->getBedroomsCount())
                ->setPricePerNight($updatedHouse->getPricePerNight())
                ->setHasAirConditioning($updatedHouse->hasAirConditioning())
                ->setHasWifi($updatedHouse->hasWifi())
                ->setHasKitchen($updatedHouse->hasKitchen())
                ->setHasParking($updatedHouse->hasParking())
                ->setHasSeaView($updatedHouse->hasSeaView());

            $entityManager->flush();
        }
    }

    public function deleteHouse(int $id): void
    {
        $entityManager = $this->getEntityManager();
        $house         = $this->find($id);

        if ($house) {
            $entityManager->remove($house);
            $entityManager->flush();
        }
    }

    public function loadFromCsv(string $filePath): void
    {
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $house = (new House())
                    ->setId((int) $data[0])
                    ->setIsAvailable((bool) $data[1])
                    ->setBedroomsCount((int) $data[2])
                    ->setPricePerNight((int) $data[3])
                    ->setHasAirConditioning((bool) $data[4])
                    ->setHasWifi((bool) $data[5])
                    ->setHasKitchen((bool) $data[6])
                    ->setHasParking((bool) $data[7])
                    ->setHasSeaView((bool) $data[8]);

                $this->addHouse($house);
            }
            fclose($handle);
        }
    }
}
