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
        if ($booking instanceof JsonResponse) {
            return $booking;
        }

        if ($error = $this->validateBooking($booking)) {
            return $error;
        }

        if ($error = $this->checkHouseAvailability($booking->getHouse())) {
            return $error;
        }

        $booking->getHouse()->setIsAvailable(false);
        $this->housesRepository->updateHouse($booking->getHouse());
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
        return $booking
        ? new JsonResponse(
            $booking->toArray(),
            Response::HTTP_OK
        )
        : new JsonResponse(
            BookingsMessages::notFound(),
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route('/{id}', name: 'bookings_replace_by_id', methods: ['PUT'])]
    public function replaceBooking(Request $request, int $id): JsonResponse
    {
        $booking = $this->deserializeBooking($request);
        if ($booking instanceof JsonResponse) {
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

        if ($error = $this->validateBooking($booking)) {
            return $error;
        }

        if ($error = $this->switchHouse($existingBooking, $booking)) {
            return $error;
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
        $booking = $this->deserializeBooking($request);
        if ($booking instanceof JsonResponse) {
            return $booking;
        }

        $existingBooking = $this->bookingsRepository->findBookingById($id);
        if (! $existingBooking) {
            return new JsonResponse(
                BookingsMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        $existingBooking
            ->setPhoneNumber(
                $booking->getPhoneNumber() ??
                $existingBooking->getPhoneNumber()
            )
            ->setComment(
                $booking->getComment() ??
                $existingBooking->getComment()
            );

        if (
            $booking->getHouse() &&
            $existingBooking->getHouse()->getId() !== $booking->getHouse()->getId()
        ) {
            if ($error = $this->switchHouse($existingBooking, $booking)) {
                return $error;
            }

            $existingBooking->setHouse($booking->getHouse());
        }

        if ($error = $this->validateBooking($existingBooking)) {
            return $error;
        }

        $this->bookingsRepository->updateBooking($existingBooking);
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

        $booking->getHouse()->setIsAvailable(true);
        $this->housesRepository->updateHouse($booking->getHouse());
        $this->bookingsRepository->deleteBookingById($id);

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
            $data = array_filter(
                json_decode(
                    $request->getContent(),
                    true
                )
            );

            $house = null;
            if (isset($data['house_id'])) {
                $house = $this->housesRepository->findHouseById((int) $data['house_id']);
                unset($data['house_id']);
            }

            $booking = $this->serializer->deserialize(
                json_encode($data),
                Booking::class,
                'json'
            );
            $booking->setHouse($house);

            return $booking;
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return new JsonResponse(
                BookingsMessages::buildMessage(
                    "Deserialization failed",
                    [$e->getMessage()]
                ),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    private function validateBooking(Booking $booking): ?JsonResponse
    {
        $errors = $this->validator->validate($booking);

        if (count($errors) === 0) {
            return null;
        }

        $errorsArray = [];
        foreach ($errors as $error) {
            $errorsArray[] = [
                'field'   => (new UnicodeString($error->getPropertyPath()))->snake(),
                'message' => $error->getMessage(),
            ];
        }

        return new JsonResponse(
            BookingsMessages::buildMessage(
                'Validation failed',
                $errorsArray
            ),
            Response::HTTP_BAD_REQUEST
        );
    }

    private function checkHouseAvailability(?House $house): ?JsonResponse
    {
        if (! $house) {
            return new JsonResponse(
                HousesMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }
        if (! $house->isAvailable()) {
            return new JsonResponse(
                HousesMessages::notAvailable(),
                Response::HTTP_BAD_REQUEST
            );
        }
        return null;
    }

    private function switchHouse(Booking $oldBooking, Booking $newBooking): ?JsonResponse
    {
        $oldHouse = $oldBooking->getHouse();
        $newHouse = $newBooking->getHouse();

        if (! $newHouse || $oldHouse->getId() === $newHouse->getId()) {
            return null;
        }

        if ($error = $this->checkHouseAvailability($newHouse)) {
            return $error;
        }

        $oldHouse->setIsAvailable(true);
        $newHouse->setIsAvailable(false);

        $this->housesRepository->updateHouse($oldHouse);
        $this->housesRepository->updateHouse($newHouse);

        return null;
    }
}
