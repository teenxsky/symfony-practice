<?php
namespace App\Repository;

use App\Entity\Booking;
use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingsRepository extends ServiceEntityRepository
{
    private const BOOKING_FIELDS = [
        'id',
        'phone_number',
        'house_id',
        'comment',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return self::BOOKING_FIELDS;
    }

    /**
     * @return Booking[]
     */
    public function findAllBookings(): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBookingById(int $id): ?Booking
    {
        return $this->find($id);
    }

    public function addBooking(Booking $booking): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($booking);
        $entityManager->flush();
    }

    public function updateBooking(Booking $updatedBooking): void
    {
        $entityManager = $this->getEntityManager();

        /** @var Booking|null $booking */
        $booking = $this->find($updatedBooking->getId());
        if ($booking) {
            ($booking)
                ->setPhoneNumber($updatedBooking->getPhoneNumber())
                ->setComment($updatedBooking->getComment())
                ->setHouse($updatedBooking->getHouse());

            $entityManager->flush();
        }
    }

    public function deleteBookingById(int $id): void
    {
        $entityManager = $this->getEntityManager();
        $booking       = $this->find($id);

        if ($booking) {
            $entityManager->remove($booking);
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
                keys: self::BOOKING_FIELDS,
                values: $data
            );

            $house = $this->getEntityManager()
                ->getRepository(House::class)
                ->find((int) $row['house_id']);
            if (! $house) {
                continue;
            }

            $booking = (new Booking())
                ->setId((int) $row['id'])
                ->setPhoneNumber((string) $row['phone_number'])
                ->setHouse($house)
                ->setComment((string) $row['comment']);

            $this->addBooking($booking);
        }

        fclose($handle);
    }
}
