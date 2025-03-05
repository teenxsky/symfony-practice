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

        /** @var House|null $house */
        $house = $this->find($updatedHouse->getId());
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
        $csvFile = fopen($filePath, 'r');
        if ($csvFile === false) {
            throw new \RuntimeException("Unable to open the CSV file: $filePath");
        }

        fgetcsv($csvFile, 0, ',', '"', '\\');

        while (($data = fgetcsv($csvFile, 0, ',', '"', '\\')) !== false) {
            $house = (new House())
                ->setIsAvailable($data[1] === '1')
                ->setBedroomsCount((int) $data[2])
                ->setPricePerNight((int) $data[3])
                ->setHasAirConditioning($data[4] === '1')
                ->setHasWifi($data[5] === '1')
                ->setHasKitchen($data[6] === '1')
                ->setHasParking($data[7] === '1')
                ->setHasSeaView($data[8] === '1');

            $this->addHouse($house);
        }

        fclose($csvFile);
    }
}
