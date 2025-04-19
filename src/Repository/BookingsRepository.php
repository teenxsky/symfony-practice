<?php
namespace App\Repository;

use App\Entity\Booking;
use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

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
        $booking       = $this->find($updatedBooking->getId());
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
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $house = $this->getEntityManager()
                    ->getRepository(House::class)
                    ->find((int) $data[2]);

                $booking = (new Booking())
                    ->setId((int) $data[0])
                    ->setPhoneNumber((string) $data[1])
                    ->setHouse($house)
                    ->setComment((string) $data[3]);

                $this->addBooking($booking);
            }
            fclose($handle);
        }
    }
}
