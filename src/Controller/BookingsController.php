<?php
namespace App\Controller;

use App\Entity\Booking;
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
    private $bookings_repository;
    private $houses_repository;
    private $serializer;
    private $validator;

    public function __construct(
        BookingsRepository $bookings_repository,
        HousesRepository $houses_repository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->bookings_repository = $bookings_repository;
        $this->houses_repository   = $houses_repository;
        $this->serializer          = $serializer;
        $this->validator           = $validator;
    }

    #[Route('/', name: 'bookings_list', methods: ['GET'])]
    public function listBookings(): JsonResponse
    {
        return new JsonResponse(
            $this->bookings_repository->findAllBookings(),
            Response::HTTP_OK
        );
    }

    #[Route('/', name: 'bookings_add', methods: ['POST'])]
    public function addBooking(Request $request): JsonResponse
    {
        [
            'booking' => $booking,
            'error'   => $err,
        ] = $this->bookingDeserialize($request);

        if ($err) {
            return new JsonResponse($err, Response::HTTP_BAD_REQUEST);
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

        $booked_house = $this->houses_repository->findHouseById($booking->getHouseId());
        if (! $booked_house) {
            return new JsonResponse(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        if (! $booked_house->isAvailable()) {
            return new JsonResponse(
                ['status' => 'House is not available'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $booked_house->setIsAvailable(false);
        $this->houses_repository->updateHouse($booked_house);

        $this->bookings_repository->addBooking($booking);
        return new JsonResponse(
            ['status' => 'Booking created!'],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'bookings_get_by_id', methods: ['GET'])]
    public function getBooking(int $id): JsonResponse
    {
        $booking = $this->bookings_repository->findBookingById($id);
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
        [
            'booking' => $replacing_booking,
            'error'   => $err,
        ] = $this->bookingDeserialize($request);

        if ($err) {
            return new JsonResponse($err, Response::HTTP_BAD_REQUEST);
        }

        $existing_booking = $this->bookings_repository->findBookingById($id);
        if (! $existing_booking) {
            return new JsonResponse(
                ['status' => 'Booking not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $replacing_booking->setId($id);

        $errs = $this->validateBooking($replacing_booking);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($existing_booking->getHouseId() !== $replacing_booking->getHouseId()) {
            $new_booking_house = $this->houses_repository->findHouseById($replacing_booking->getHouseId());
            $old_booking_house = $this->houses_repository->findHouseById($existing_booking->getHouseId());

            if (! $new_booking_house) {
                return new JsonResponse(
                    ['status' => 'House not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            if (! $new_booking_house->isAvailable()) {
                return new JsonResponse(
                    ['status' => 'House is not available'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $old_booking_house->setIsAvailable(true);
            $this->houses_repository->updateHouse($old_booking_house);
            $new_booking_house->setIsAvailable(false);
            $this->houses_repository->updateHouse($new_booking_house);
        }

        $this->bookings_repository->updateBooking($replacing_booking);
        return new JsonResponse(
            ['status' => 'Booking replaced!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'bookings_update_by_id', methods: ['PATCH'])]
    public function updateBooking(Request $request, int $id): JsonResponse
    {
        [
            'booking' => $updated_booking,
            'error'   => $err,
        ] = $this->bookingDeserialize($request);

        if ($err) {
            return new JsonResponse($err, Response::HTTP_BAD_REQUEST);
        }

        $existing_booking = $this->bookings_repository->findBookingById($id);
        if (! $existing_booking) {
            return new JsonResponse(
                ['status' => 'Booking not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $existing_booking
            ->setPhoneNumber($updated_booking->getPhoneNumber() ?? $existing_booking->getPhoneNumber())
            ->setComment($updated_booking->getComment() ?? $existing_booking->getComment());

        if ($updated_booking->getHouseId()) {
            if ($existing_booking->getHouseId() !== $updated_booking->getHouseId()) {
                $new_booking_house = $this->houses_repository->findHouseById($updated_booking->getHouseId());
                $old_booking_house = $this->houses_repository->findHouseById($existing_booking->getHouseId());

                if (! $new_booking_house) {
                    return new JsonResponse(
                        ['status' => 'House not found'],
                        Response::HTTP_NOT_FOUND
                    );
                }
                if (! $new_booking_house->isAvailable()) {
                    return new JsonResponse(
                        ['status' => 'House is not available'],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $old_booking_house->setIsAvailable(true);
                $this->houses_repository->updateHouse($old_booking_house);
                $new_booking_house->setIsAvailable(false);
                $this->houses_repository->updateHouse($new_booking_house);
            }
            $existing_booking->setHouseId($updated_booking->getHouseId());
        }

        $errs = $this->validateBooking($existing_booking);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->bookings_repository->updateBooking($existing_booking);
        return new JsonResponse(
            ['status' => 'Booking updated!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'bookings_delete_by_id', methods: ['DELETE'])]
    public function deleteBooking(Request $request, int $id): JsonResponse
    {
        if (! $booking = $this->bookings_repository->findBookingById($id)) {
            return new JsonResponse(
                ['status' => 'Booking not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $booked_house = $this->houses_repository->findHouseById($booking->getHouseId());
        $booked_house->setIsAvailable(true);
        $this->houses_repository->updateHouse($booked_house);

        $this->bookings_repository->deleteBooking($id);
        return new JsonResponse(
            ['status' => 'Booking deleted!'],
            Response::HTTP_OK
        );
    }

    private function bookingDeserialize(Request $request): array
    {
        if ($request->getContentTypeFormat() !== 'json') {
            return [
                'booking' => null,
                'error'   => [
                    'status' => 'Unsupported content format',
                ],
            ];
        }
        try {
            $booking = $this->serializer->deserialize(
                $request->getContent(),
                Booking::class,
                'json'
            );
            return [
                'booking' => $booking,
                'error'   => null,
            ];
        } catch (NotEncodableValueException | UnexpectedValueException $err) {
            return [
                'booking' => null,
                'error'   => [
                    'status' => 'Invalid JSON',
                    'error'  => $err->getMessage(),
                ],
            ];
        }
    }

    private function validateBooking(Booking $booking): array
    {
        $errs = $this->validator->validate($booking);
        if (count($errs) > 0) {
            $errs_array = [];
            foreach ($errs as $err) {
                $errs_array[] = [
                    'field'   => $err->getPropertyPath(),
                    'message' => $err->getMessage(),
                ];
            }
            return $errs_array;
        }
        return [];
    }
}
