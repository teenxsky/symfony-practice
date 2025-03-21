<?php
namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingsRepository;
use App\Repository\HousesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/bookings', name: 'bookings_api')]
class BookingsController extends AbstractController
{
    private $bookingsRepository;
    private $housesRepository;
    private $validator;
    private $serializer;

    public function __construct(BookingsRepository $bookingsRepository, HousesRepository $housesRepository, ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->bookingsRepository = $bookingsRepository;
        $this->housesRepository   = $housesRepository;
        $this->validator          = $validator;
        $this->serializer         = $serializer;
    }

    #[Route('/', name: 'bookings_list', methods: ['GET'])]
    public function listBookings(): JsonResponse
    {
        return $this->json($this->bookingsRepository->findAllbookings());
    }

    #[Route('/', name: 'bookings_add', methods: ['POST'])]
    public function addBooking(Request $request): JsonResponse
    {
        if ('json' !== $request->getContentTypeFormat()) {
            return $this->json(['status' => 'Unsupported content format'], 400);
        }

        try {
            $booking = $this->serializer->deserialize($request->getContent(), Booking::class, 'json');
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return $this->json(['status' => 'Invalid JSON', 'error' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($booking);

        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = [
                    'field'   => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }
            return $this->json(['status' => 'Validation failed', 'errors' => $errorsArray], 400);
        }

        $bookedHouse = $this->housesRepository->findHouseById($booking->getHouseId());
        if (! $bookedHouse) {
            return $this->json(['status' => 'House not found'], 404);
        }
        if (! $bookedHouse->isAvailable()) {
            return $this->json(['status' => 'House is not available'], 400);
        }

        $bookedHouse->setIsAvailable(false);
        $this->housesRepository->updateHouse($bookedHouse);
        $this->bookingsRepository->addBooking($booking);

        return $this->json(['status' => 'Booking created!'], 201);
    }

    #[Route('/{id}', name: 'bookings_get_by_id', methods: ['GET'])]
    public function getBooking(int $id): JsonResponse
    {
        $booking = $this->bookingsRepository->findBookingById($id);
        if (! $booking) {
            return $this->json(['status' => 'Booking not found'], 404);
        }

        return $this->json($booking);
    }

    #[Route('/{id}', name: 'bookings_replace_by_id', methods: ['PUT'])]
    public function replaceBooking(Request $request, int $id): JsonResponse
    {
        if ('json' !== $request->getContentTypeFormat()) {
            return $this->json(['status' => 'Unsupported content format'], 400);
        }

        $existingBooking = $this->bookingsRepository->findBookingById($id);
        if (! $existingBooking) {
            return $this->json(['status' => 'Booking not found'], 404);
        }

        try {
            $updatedBooking = $this->serializer->deserialize($request->getContent(), Booking::class, 'json');
            $updatedBooking->setId($id);
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return $this->json(['status' => 'Invalid JSON', 'error' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($updatedBooking);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = [
                    'field'   => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }
            return $this->json(['status' => 'Validation failed', 'errors' => $errorsArray], 400);
        }

        if ($existingBooking->getHouseId() !== $updatedBooking->getHouseId()) {
            $newBookingHouse = $this->housesRepository->findHouseById($updatedBooking->getHouseId());
            $oldBookingHouse = $this->housesRepository->findHouseById($existingBooking->getHouseId());

            if (! $newBookingHouse) {
                return $this->json(['status' => 'House not found'], 404);
            }
            if (! $newBookingHouse->isAvailable()) {
                return $this->json(['status' => 'House is not available'], 400);
            }

            $oldBookingHouse->setIsAvailable(true);
            $this->housesRepository->updateHouse($oldBookingHouse);
            $newBookingHouse->setIsAvailable(false);
            $this->housesRepository->updateHouse($newBookingHouse);
        }

        $this->bookingsRepository->updateBooking($existingBooking);

        return $this->json(['status' => 'Booking replaced!'], 200);
    }

    #[Route('/{id}', name: 'bookings_update_by_id', methods: ['PATCH'])]
    public function updateBooking(Request $request, int $id): JsonResponse
    {
        if ('json' !== $request->getContentTypeFormat()) {
            return $this->json(['status' => 'Unsupported content format'], 400);
        }

        $existingBooking = $this->bookingsRepository->findBookingById($id);
        if (! $existingBooking) {
            return $this->json(['status' => 'Booking not found'], 404);
        }

        try {
            $updatedBooking = $this->serializer->deserialize($request->getContent(), Booking::class, 'json');
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return $this->json(['status' => 'Invalid JSON', 'error' => $e->getMessage()], 400);
        }

        if ($updatedBooking->getPhoneNumber() !== null) {
            $existingBooking->setPhoneNumber($updatedBooking->getPhoneNumber());
        }
        if ($updatedBooking->getComment() !== null) {
            $existingBooking->setComment($updatedBooking->getComment());
        }
        if ($updatedBooking->getHouseId() !== null) {
            if ($existingBooking->getHouseId() !== $updatedBooking->getHouseId()) {
                $newBookingHouse = $this->housesRepository->findHouseById($updatedBooking->getHouseId());
                $oldBookingHouse = $this->housesRepository->findHouseById($existingBooking->getHouseId());

                if (! $newBookingHouse) {
                    return $this->json(['status' => 'House not found'], 404);
                }
                if (! $newBookingHouse->isAvailable()) {
                    return $this->json(['status' => 'House is not available'], 400);
                }

                $oldBookingHouse->setIsAvailable(true);
                $this->housesRepository->updateHouse($oldBookingHouse);
                $newBookingHouse->setIsAvailable(false);
                $this->housesRepository->updateHouse($newBookingHouse);
            }
            $existingBooking->setHouseId($updatedBooking->getHouseId());
        }

        $errors = $this->validator->validate($existingBooking);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = [
                    'field'   => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }
            return $this->json(['status' => 'Validation failed', 'errors' => $errorsArray], 400);
        }

        $this->bookingsRepository->updateBooking($existingBooking);

        return $this->json(['status' => 'Booking updated!'], 200);
    }

    #[Route('/{id}', name: 'bookings_delete_by_id', methods: ['DELETE'])]
    public function deleteBooking(Request $request, int $id): JsonResponse
    {
        if (! $booking = $this->bookingsRepository->findBookingById($id)) {
            return $this->json(['status' => 'Booking not found'], 404);
        }
        
        $bookedHouse = $this->housesRepository->findHouseById($booking->getHouseId());
        $bookedHouse->setIsAvailable(true);
        $this->housesRepository->updateHouse($bookedHouse);

        $this->bookingsRepository->deleteBooking($id);

        return $this->json(['status' => 'Booking deleted!'], 200);
    }
}
