<?php
namespace App\Repository;

use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HousesRepository extends ServiceEntityRepository
{
    private const HOUSE_FIELDS = [
        'id',
        'is_available',
        'bedrooms_count',
        'price_per_night',
        'has_air_conditioning',
        'has_wifi',
        'has_kitchen',
        'has_parking',
        'has_sea_view',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, House::class);
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return self::HOUSE_FIELDS;
    }

    /**
     * @return House[]
     */
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

    public function deleteHouseById(int $id): void
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
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException("Unable to open the CSV file: $filePath");
        }

        fgetcsv($handle, 0, ',', '"', '\\');

        while ($data = fgetcsv($handle, 0, ',', '"', '\\')) {
            $row = array_combine(
                keys: self::HOUSE_FIELDS,
                values: $data
            );

            $house = (new House())
                ->setIsAvailable(
                    (bool) $row['is_available']
                )
                ->setBedroomsCount(
                    (int) $row['bedrooms_count']
                )
                ->setPricePerNight(
                    (int) $row['price_per_night']
                )
                ->setHasAirConditioning(
                    (bool) $row['has_air_conditioning']
                )
                ->setHasWifi(
                    (bool) $row['has_wifi']
                )
                ->setHasKitchen(
                    (bool) $row['has_kitchen']
                )
                ->setHasParking(
                    (bool) $row['has_parking']
                )
                ->setHasSeaView(
                    (bool) $row['has_sea_view']
                );

            $this->addHouse($house);
        }

        fclose($handle);
    }
}
