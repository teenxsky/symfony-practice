<?php

declare (strict_types=1);

namespace App\Entity;

use App\Repository\HousesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HousesRepository::class)]
class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => true])]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $isAvailable = true;

    #[ORM\Column(length: 20)]
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\Range(min: 1, max: 20)]
    private ?int $bedroomsCount = null;

    #[ORM\Column(length: 100000)]
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\Range(min: 100, max: 100000)]
    private ?int $pricePerNight = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $hasAirConditioning = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $hasWifi = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $hasKitchen = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $hasParking = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $hasSeaView = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    #[Ignore]
    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    public function getBedroomsCount(): ?int
    {
        return $this->bedroomsCount;
    }

    public function setBedroomsCount(int $bedroomsCount): static
    {
        $this->bedroomsCount = $bedroomsCount;

        return $this;
    }

    public function getPricePerNight(): ?int
    {
        return $this->pricePerNight;
    }

    public function setPricePerNight(int $pricePerNight): static
    {
        $this->pricePerNight = $pricePerNight;

        return $this;
    }

    #[Ignore]
    public function hasAirConditioning(): ?bool
    {
        return $this->hasAirConditioning;
    }

    public function setHasAirConditioning(?bool $hasAirConditioning): static
    {
        $this->hasAirConditioning = $hasAirConditioning;

        return $this;
    }

    #[Ignore]
    public function hasWifi(): ?bool
    {
        return $this->hasWifi;
    }

    public function setHasWifi(?bool $hasWifi): static
    {
        $this->hasWifi = $hasWifi;

        return $this;
    }

    #[Ignore]
    public function hasKitchen(): ?bool
    {
        return $this->hasKitchen;
    }

    public function setHasKitchen(?bool $hasKitchen): static
    {
        $this->hasKitchen = $hasKitchen;

        return $this;
    }

    #[Ignore]
    public function hasParking(): ?bool
    {
        return $this->hasParking;
    }

    public function setHasParking(?bool $hasParking): static
    {
        $this->hasParking = $hasParking;

        return $this;
    }

    #[Ignore]
    public function hasSeaView(): ?bool
    {
        return $this->hasSeaView;
    }

    public function setHasSeaView(?bool $hasSeaView): static
    {
        $this->hasSeaView = $hasSeaView;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->getId(),
            'isAvailable'        => $this->isAvailable(),
            'bedroomsCount'      => $this->getBedroomsCount(),
            'pricePerNight'      => $this->getPricePerNight(),
            'hasAirConditioning' => $this->hasAirConditioning(),
            'hasWifi'            => $this->hasWifi(),
            'hasKitchen'         => $this->hasKitchen(),
            'hasParking'         => $this->hasParking(),
            'hasSeaView'         => $this->hasSeaView(),
        ];
    }
}
