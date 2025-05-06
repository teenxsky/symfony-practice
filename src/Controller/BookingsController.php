<?php
namespace App\Controller;

use App\Constant\BookingsMessages;
use App\Constant\HousesMessages;
use App\Entity\Booking;
use App\Entity\House;
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
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/bookings', name: 'bookings_api')]
class BookingsController extends AbstractController
{
    public function __construct(
        private BookingsRepository $bookingsRepository,
        private HousesRepository $housesRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'bookings_list', methods: ['GET'])]
    public function listBookings(): JsonResponse
    {
        $bookingsArray = array_map(
            fn($booking) => $booking->toArray(),
            $this->bookingsRepository->findAllBookings()
        );

        return new JsonResponse($bookingsArray, Response::HTTP_OK);
    }

    #[Route('/', name: 'bookings_add', methods: ['POST'])]
    public function addBooking(Request $request): JsonResponse
    {
        $booking = $this->deserializeBooking($request);
        if (! $booking instanceof Booking) {
            return $booking;
        }

        if ($errors = $this->validateBooking($booking)) {
            return new JsonResponse(
                HousesMessages::buildMessage(
                    'Validation failed',
                    $errors
                ),
                Response::HTTP_BAD_REQUEST);
        }

        $houseCheck = $this->checkHouseAvailability($booking->getHouseId());
        if ($houseCheck instanceof JsonResponse) {
            return $houseCheck;
        }

        $houseCheck->setIsAvailable(false);
        $this->housesRepository->updateHouse($houseCheck);

        $this->bookingsRepository->addBooking($booking);

        return new JsonResponse(
            BookingsMessages::created(),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'bookings_get_by_id', methods: ['GET'])]
    public function getBooking(int $id): JsonResponse
    {
        $booking = $this->bookingsRepository->findBookingById($id);
        if (! $booking) {
            return new JsonResponse(
                BookingsMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($booking->toArray(), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'bookings_replace_by_id', methods: ['PUT'])]
    public function replaceBooking(Request $request, int $id): JsonResponse
    {
        $booking = $this->deserializeBooking($request);
        if (! $booking instanceof Booking) {
            return $booking;
        }

        $existingBooking = $this->bookingsRepository->findBookingById($id);
        if (! $existingBooking) {
            return new JsonResponse(
                BookingsMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        $booking->setId($id);

        if ($errors = $this->validateBooking($booking)) {
            return new JsonResponse(
                BookingsMessages::buildMessage(
                    'Validation failed',
                    $errors
                ),
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($existingBooking->getHouseId() !== $booking->getHouseId()) {
            $houseSwitchResult = $this->switchBookingHouse($existingBooking->getHouseId(), $booking->getHouseId());
            if ($houseSwitchResult instanceof JsonResponse) {
                return $houseSwitchResult;
            }
        }

        $this->bookingsRepository->updateBooking($booking);
        return new JsonResponse(
            BookingsMessages::replaced(),
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'bookings_update_by_id', methods: ['PATCH'])]
    public function updateBooking(Request $request, int $id): JsonResponse
    {
        $updated = $this->deserializeBooking($request);
        if (! $updated instanceof Booking) {
            return $updated;
        }

        $booking = $this->bookingsRepository->findBookingById($id);
        if (! $booking) {
            return new JsonResponse(
                BookingsMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        $booking
            ->setPhoneNumber(
                $updated->getPhoneNumber() ??
                $booking->getPhoneNumber()
            )
            ->setComment(
                $updated->getComment() ??
                $booking->getComment()
            );

        if (
            $updated->getHouseId() &&
            $updated->getHouseId() !== $booking->getHouseId()
        ) {
            $houseSwitchResult = $this->switchBookingHouse(
                $booking->getHouseId(),
                $updated->getHouseId()
            );
            if ($houseSwitchResult instanceof JsonResponse) {
                return $houseSwitchResult;
            }
            $booking->setHouseId($updated->getHouseId());
        }

        if ($errors = $this->validateBooking($booking)) {
            return new JsonResponse(
                BookingsMessages::buildMessage(
                    'Validation failed',
                    $errors
                ),
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->bookingsRepository->updateBooking($booking);
        return new JsonResponse(
            BookingsMessages::updated(),
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'bookings_delete_by_id', methods: ['DELETE'])]
    public function deleteBooking(int $id): JsonResponse
    {
        $booking = $this->bookingsRepository->findBookingById($id);
        if (! $booking) {
            return new JsonResponse(
                BookingsMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        $house = $this->housesRepository->findHouseById($booking->getHouseId());
        if ($house) {
            $house->setIsAvailable(true);
            $this->housesRepository->updateHouse($house);
        }

        $this->bookingsRepository->deleteBooking($id);
        return new JsonResponse(
            BookingsMessages::deleted(),
            Response::HTTP_OK
        );
    }

    private function deserializeBooking(Request $request): Booking | JsonResponse
    {
        if ($request->getContentTypeFormat() !== 'json') {
            return new JsonResponse(
                BookingsMessages::buildMessage(
                    "Deserialization failed",
                    ["Unsupported content type"]
                ),
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        }

        try {
            $data = array_filter(json_decode($request->getContent(), true));
            return $this->serializer->deserialize(
                json_encode($data),
                Booking::class,
                'json'
            );
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return new JsonResponse(
                HousesMessages::buildMessage(
                    'Deserialization failed',
                    ['error' => $e->getMessage()]
                ),
                Response::HTTP_BAD_REQUEST);
        }
    }

    private function validateBooking(Booking $booking): array
    {
        $errors = $this->validator->validate($booking);
        $result = [];

        foreach ($errors as $error) {
            $result[] = [
                'field'   => (new UnicodeString($error->getPropertyPath()))->snake(),
                'message' => $error->getMessage(),
            ];
        }

        return $result;
    }

    private function checkHouseAvailability(int $houseId): JsonResponse | House
    {
        $house = $this->housesRepository->findHouseById($houseId);

        if (! $house) {
            return new JsonResponse(
                HousesMessages::notFound(), Response::HTTP_NOT_FOUND
            );
        }
        if (! $house->isAvailable()) {
            return new JsonResponse(
                HousesMessages::notAvailable(),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $house;
    }

    private function switchBookingHouse(int $oldHouseId, int $newHouseId): JsonResponse | null
    {
        $newHouse = $this->housesRepository->findHouseById($newHouseId);
        $oldHouse = $this->housesRepository->findHouseById($oldHouseId);

        if (! $newHouse) {
            return new JsonResponse(
                HousesMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        if (! $newHouse->isAvailable()) {
            return new JsonResponse(
                HousesMessages::notAvailable(),
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($oldHouse) {
            $oldHouse->setIsAvailable(true);
            $this->housesRepository->updateHouse($oldHouse);
        }

        $newHouse->setIsAvailable(false);
        $this->housesRepository->updateHouse($newHouse);

        return null;
    }
}
