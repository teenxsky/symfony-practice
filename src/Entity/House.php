<?php
namespace App\Entity;

use App\Repository\HousesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HousesRepository::class)]
class House
{
    #[ORM\OneToMany(mappedBy: 'house', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

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

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (! $this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setHouse($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getHouse() === $this) {
                $booking->setHouse(null);
            }
        }

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

    /**
     * @return array{
     *     id: int,
     *     is_available: bool,
     *     bedrooms_count: int,
     *     price_per_night: float,
     *     has_air_conditioning: bool,
     *     has_wifi: bool,
     *     has_kitchen: bool,
     *     has_parking: bool,
     *     has_sea_view: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id'                   => $this->getId(),
            'is_available'         => $this->isAvailable(),
            'bedrooms_count'       => $this->getBedroomsCount(),
            'price_per_night'      => $this->getPricePerNight(),
            'has_air_conditioning' => $this->hasAirConditioning(),
            'has_wifi'             => $this->hasWifi(),
            'has_kitchen'          => $this->hasKitchen(),
            'has_parking'          => $this->hasParking(),
            'has_sea_view'         => $this->hasSeaView(),
        ];
    }
}
