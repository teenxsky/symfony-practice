<?php
namespace App\Repository;

use App\Entity\House;

class HousesRepository
{
    private string $filePath;

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

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        if (! file_exists($this->filePath)) {
            $handle = fopen($this->filePath, 'w');
            fputcsv($handle, self::HOUSE_FIELDS, ',', '"', '\\');
            fclose($handle);
        }
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
        $houses = [];
        if (($handle = fopen($this->filePath, 'r')) !== false) {
            fgetcsv($handle, 1000, ',', '"', '\\');

            while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
                $row = array_combine(
                    keys: self::HOUSE_FIELDS,
                    values: $data
                );

                $house = (new House())
                    ->setId((int) $row['id'])
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
                $houses[] = $house;
            }
            fclose($handle);
        }
        return $houses;
    }

    public function findHouseById(int $id): ?House
    {
        $houses = $this->findAllHouses();
        foreach ($houses as $house) {
            if ($house->getId() == $id) {
                return $house;
            }
        }
        return null;
    }

    public function addHouse(House $house): void
    {
        $id     = 1;
        $houses = $this->findAllHouses();
        if (! empty($houses)) {
            $lastHouse = end($houses);
            $id        = (int) $lastHouse->getId() + 1;
        }

        $house->setId($id);

        $this->saveHouses([$house], 'a');
    }

    public function updateHouse(House $house): void
    {
        $houses = $this->findAllHouses();
        foreach ($houses as &$existingHouse) {
            if ($existingHouse->getId() == $house->getId()) {
                $existingHouse = $house;
                break;
            }
        }

        $this->saveHouses($houses, 'w');
    }

    public function deleteHouse(int $id): void
    {
        $houses = $this->findAllHouses();
        foreach ($houses as $key => $house) {
            if ($house->getId() == $id) {
                unset($houses[$key]);
                break;
            }
        }

        $this->saveHouses($houses, 'w');
    }

    private function saveHouses(array $houses, string $mode): void
    {
        if (! in_array($mode, ['w', 'a'])) {
            throw new \InvalidArgumentException('Invalid mode. Use "w" or "a".');
        }

        $handle = fopen($this->filePath, $mode);

        if ($mode === 'w') {
            fputcsv($handle, self::HOUSE_FIELDS, ',', '"', '\\');
        }

        foreach ($houses as $house) {
            $houseData = [
                $house->getId(),
                $house->isAvailable(),
                $house->getBedroomsCount(),
                $house->getPricePerNight(),
                $house->hasAirConditioning(),
                $house->hasWifi(),
                $house->hasKitchen(),
                $house->hasParking(),
                $house->hasSeaView(),
            ];
            fputcsv($handle, $houseData, ',', '"', '\\');
        }
        fclose($handle);
    }
}
