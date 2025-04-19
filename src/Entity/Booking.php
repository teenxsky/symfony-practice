<?php
namespace App\Entity;

use App\Entity\House;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingsRepository::class)]
class Booking
{
    // TODO: Add time for booking

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: House::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?House $house = null;

    #[ORM\Column(length: 15)]
    #[Assert\NotNull]
    #[Assert\Regex(
        pattern: '/^\+?[0-9]{1,3}?[0-9]{7,14}$/',
        message: 'Invalid phone number format'
    )]
    #[Assert\Length(
        min: 7,
        max: 15,
        minMessage: 'Phone number must be at least {{ limit }} characters long',
        maxMessage: 'Phone number cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Type('string')]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Comment cannot be longer than {{ limit }} characters'
    )]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function setHouse(?House $house): static
    {
        $this->house = $house;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->getId(),
            'phoneNumber' => $this->getPhoneNumber(),
            'comment'     => $this->getComment(),
            'house'       => $this->getHouse()->toArray(),
        ];
    }
}
