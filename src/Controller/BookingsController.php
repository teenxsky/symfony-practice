<?php
namespace App\Controller;

use App\Entity\Booking;
use App\Exception\DeserializeContentException;
use App\Repository\BookingsRepository;
use App\Repository\HousesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    private $serializer;
    private $validator;

    public function __construct(
        BookingsRepository $bookingsRepository,
        HousesRepository $housesRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->bookingsRepository = $bookingsRepository;
        $this->housesRepository   = $housesRepository;
        $this->serializer         = $serializer;
        $this->validator          = $validator;
    }

    #[Route('/', name: 'bookings_list', methods: ['GET'])]
    public function listBookings(): JsonResponse
    {
        return new JsonResponse(
            $this->bookingsRepository->findAllBookings(),
            Response::HTTP_OK
        );
    }

    #[Route('/', name: 'bookings_add', methods: ['POST'])]
    public function addBooking(Request $request): JsonResponse
    {
        try {
            $booking = $this->bookingDeserialize($request);
        } catch (DeserializeContentException $e) {
            return new JsonResponse(
                ['status' => $e->getMessage()],
                $e->getStatusCode()
            );
        }

        $errs = $this->validateBooking($booking);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $bookedHouse = $this->housesRepository->findHouseById($booking->getHouseId());
        if (! $bookedHouse) {
            return new JsonResponse(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        if (! $bookedHouse->isAvailable()) {
            return new JsonResponse(
                ['status' => 'House is not available'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $bookedHouse->setIsAvailable(false);
        $this->housesRepository->updateHouse($bookedHouse);

        $this->bookingsRepository->addBooking($booking);
        return new JsonResponse(
            ['status' => 'Booking created!'],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'bookings_get_by_id', methods: ['GET'])]
    public function getBooking(int $id): JsonResponse
    {
        $booking = $this->bookingsRepository->findBookingById($id);
        if (! $booking) {
            return new JsonResponse(
                ['status' => 'Booking not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($booking, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'bookings_replace_by_id', methods: ['PUT'])]
    public function replaceBooking(Request $request, int $id): JsonResponse
    {
        try {
            $replacingBooking = $this->bookingDeserialize($request);
        } catch (DeserializeContentException $e) {
            return new JsonResponse(
                ['status' => $e->getMessage()],
                $e->getStatusCode()
            );
        }

        $existingBooking = $this->bookingsRepository->findBookingById($id);
        if (! $existingBooking) {
            return new JsonResponse(
                ['status' => 'Booking not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $replacingBooking->setId($id);

        $errs = $this->validateBooking($replacingBooking);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($existingBooking->getHouseId() !== $replacingBooking->getHouseId()) {
            $newBookingHouse = $this->housesRepository->findHouseById($replacingBooking->getHouseId());
            $oldBookingHouse = $this->housesRepository->findHouseById($existingBooking->getHouseId());

            if (! $newBookingHouse) {
                return new JsonResponse(
                    ['status' => 'House not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            if (! $newBookingHouse->isAvailable()) {
                return new JsonResponse(
                    ['status' => 'House is not available'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $oldBookingHouse->setIsAvailable(true);
            $this->housesRepository->updateHouse($oldBookingHouse);
            $newBookingHouse->setIsAvailable(false);
            $this->housesRepository->updateHouse($newBookingHouse);
        }

        $this->bookingsRepository->updateBooking($replacingBooking);
        return new JsonResponse(
            ['status' => 'Booking replaced!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'bookings_update_by_id', methods: ['PATCH'])]
    public function updateBooking(Request $request, int $id): JsonResponse
    {
        try {
            $updatedBooking = $this->bookingDeserialize($request);
        } catch (DeserializeContentException $e) {
            return new JsonResponse(
                ['status' => $e->getMessage()],
                $e->getStatusCode()
            );
        }

        $existingBooking = $this->bookingsRepository->findBookingById($id);
        if (! $existingBooking) {
            return new JsonResponse(
                ['status' => 'Booking not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $existingBooking
            ->setPhoneNumber($updatedBooking->getPhoneNumber() ?? $existingBooking->getPhoneNumber())
            ->setComment($updatedBooking->getComment() ?? $existingBooking->getComment());

        if ($updatedBooking->getHouseId()) {
            if ($existingBooking->getHouseId() !== $updatedBooking->getHouseId()) {
                $newBookingHouse = $this->housesRepository->findHouseById($updatedBooking->getHouseId());
                $oldBookingHouse = $this->housesRepository->findHouseById($existingBooking->getHouseId());

                if (! $newBookingHouse) {
                    return new JsonResponse(
                        ['status' => 'House not found'],
                        Response::HTTP_NOT_FOUND
                    );
                }
                if (! $newBookingHouse->isAvailable()) {
                    return new JsonResponse(
                        ['status' => 'House is not available'],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $oldBookingHouse->setIsAvailable(true);
                $this->housesRepository->updateHouse($oldBookingHouse);
                $newBookingHouse->setIsAvailable(false);
                $this->housesRepository->updateHouse($newBookingHouse);
            }
            $existingBooking->setHouseId($updatedBooking->getHouseId());
        }

        $errs = $this->validateBooking($existingBooking);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->bookingsRepository->updateBooking($existingBooking);
        return new JsonResponse(
            ['status' => 'Booking updated!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'bookings_delete_by_id', methods: ['DELETE'])]
    public function deleteBooking(Request $request, int $id): JsonResponse
    {
        if (! $booking = $this->bookingsRepository->findBookingById($id)) {
            return new JsonResponse(
                ['status' => 'Booking not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $bookedHouse = $this->housesRepository->findHouseById($booking->getHouseId());
        $bookedHouse->setIsAvailable(true);
        $this->housesRepository->updateHouse($bookedHouse);

        $this->bookingsRepository->deleteBooking($id);
        return new JsonResponse(
            ['status' => 'Booking deleted!'],
            Response::HTTP_OK
        );
    }

    private function bookingDeserialize(Request $request): Booking
    {
        if ($request->getContentTypeFormat() !== 'json') {
            throw new DeserializeContentException();
        }

        try {
            return $this->serializer->deserialize(
                $request->getContent(),
                Booking::class,
                'json'
            );
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            throw new DeserializeContentException($e->getMessage());
        }
    }

    private function validateBooking(Booking $booking): array
    {
        $errs = $this->validator->validate($booking);
        if (count($errs) > 0) {
            $errsArray = [];
            foreach ($errs as $err) {
                $errsArray[] = [
                    'field'   => $err->getPropertyPath(),
                    'message' => $err->getMessage(),
                ];
            }
            return $errsArray;
        }
        return [];
    }
}
