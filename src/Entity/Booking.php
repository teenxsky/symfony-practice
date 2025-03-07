<?php
namespace App\Entity;

// use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// #[ORM\Entity(repositoryClass: BookingsRepository::class)]
class Booking
{
    // #[ORM\Id]
    // #[ORM\GeneratedValue]
    // #[ORM\Column]
    private ?int $id = null;

    // #[ORM\Column(length: 255)]
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

    // #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    private ?int $houseId = null;

    // #[ORM\Column(length: 255, nullable: true)]
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

    public function getHouseId(): ?int
    {
        return $this->houseId;
    }

    public function setHouseId(int $houseId): static
    {
        $this->houseId = $houseId;

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
            'houseId'     => $this->getHouseId(),
            'comment'     => $this->getComment(),
        ];
    }
}
