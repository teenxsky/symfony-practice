<?php
namespace App\Entity;

// use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

// #[ORM\Entity(repositoryClass: HousesRepository::class)]
class House
{
    // #[ORM\Id]
    // #[ORM\GeneratedValue]
    // #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $is_available = true;

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\Range(min: 1, max: 20)]
    private ?int $bedrooms_count = null;

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\Range(min: 100, max: 100000)]
    private ?int $price_per_night = null;

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $has_air_conditioning = null;

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $has_wifi = null;

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $has_kitchen = null;

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $has_parking = null;

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('boolean')]
    private ?bool $has_sea_view = null;

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
        return $this->is_available;
    }

    public function setIsAvailable(bool $is_available): static
    {
        $this->is_available = $is_available;

        return $this;
    }

    public function getBedroomsCount(): ?int
    {
        return $this->bedrooms_count;
    }

    public function setBedroomsCount(int $bedrooms_count): static
    {
        $this->bedrooms_count = $bedrooms_count;

        return $this;
    }

    public function getPricePerNight(): ?int
    {
        return $this->price_per_night;
    }

    public function setPricePerNight(int $price_per_night): static
    {
        $this->price_per_night = $price_per_night;

        return $this;
    }

    #[Ignore]
    public function hasAirConditioning(): ?bool
    {
        return $this->has_air_conditioning;
    }

    public function setHasAirConditioning(?bool $has_air_conditioning): static
    {
        $this->has_air_conditioning = $has_air_conditioning;

        return $this;
    }

    #[Ignore]
    public function hasWifi(): ?bool
    {
        return $this->has_wifi;
    }

    public function setHasWifi(?bool $has_wifi): static
    {
        $this->has_wifi = $has_wifi;

        return $this;
    }

    #[Ignore]
    public function hasKitchen(): ?bool
    {
        return $this->has_kitchen;
    }

    public function setHasKitchen(?bool $has_kitchen): static
    {
        $this->has_kitchen = $has_kitchen;

        return $this;
    }

    #[Ignore]
    public function hasParking(): ?bool
    {
        return $this->has_parking;
    }

    public function setHasParking(?bool $has_parking): static
    {
        $this->has_parking = $has_parking;

        return $this;
    }

    #[Ignore]
    public function hasSeaView(): ?bool
    {
        return $this->has_sea_view;
    }

    public function setHasSeaView(?bool $has_sea_view): static
    {
        $this->has_sea_view = $has_sea_view;

        return $this;
    }
}
