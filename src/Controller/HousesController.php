<?php
namespace App\Controller;

use App\Constant\HousesMessages;
use App\Entity\House;
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

#[Route('/api/v1/houses', name: 'houses_api')]
class HousesController extends AbstractController
{
    public function __construct(
        private HousesRepository $housesRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'houses_list', methods: ['GET'])]
    public function listHouses(): JsonResponse
    {
        $housesArray = array_map(
            fn($house) => $house->toArray(),
            $this->housesRepository->findAllHouses()
        );

        return new JsonResponse($housesArray, Response::HTTP_OK);
    }

    #[Route('/', name: 'houses_add', methods: ['POST'])]
    public function addHouse(Request $request): JsonResponse
    {
        $house = $this->deserializeHouse($request);
        if ($house instanceof JsonResponse) {
            return $house;
        }

        if ($error = $this->validateHouse($house)) {
            return $error;
        }

        $this->housesRepository->addHouse($house);
        return new JsonResponse(
            HousesMessages::created(),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'houses_get_by_id', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        $house = $this->housesRepository->findHouseById($id);
        return $house
        ? new JsonResponse(
            $house->toArray(),
            Response::HTTP_OK
        )
        : new JsonResponse(
            HousesMessages::notFound(),
            Response::HTTP_NOT_FOUND
        );
    }

    #[Route('/{id}', name: 'houses_replace_by_id', methods: ['PUT'])]
    public function replaceHouse(Request $request, int $id): JsonResponse
    {
        $replacingHouse = $this->deserializeHouse($request);
        if ($replacingHouse instanceof JsonResponse) {
            return $replacingHouse;
        }

        $existingHouse = $this->housesRepository->findHouseById($id);
        if (! $existingHouse) {
            return new JsonResponse(
                HousesMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        $replacingHouse->setId($id);

        if ($error = $this->validateHouse($replacingHouse)) {
            return $error;
        }

        $this->housesRepository->updateHouse($replacingHouse);
        return new JsonResponse(
            HousesMessages::replaced(),
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'houses_update_by_id', methods: ['PATCH'])]
    public function updateHouse(Request $request, int $id): JsonResponse
    {
        $updatedHouse = $this->deserializeHouse($request);
        if ($updatedHouse instanceof JsonResponse) {
            return $updatedHouse;
        }

        $existingHouse = $this->housesRepository->findHouseById($id);
        if (! $existingHouse) {
            return new JsonResponse(
                HousesMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        $existingHouse
            ->setIsAvailable(
                $updatedHouse->isAvailable() ??
                $existingHouse->isAvailable()
            )
            ->setBedroomsCount(
                $updatedHouse->getBedroomsCount() ??
                $existingHouse->getBedroomsCount()
            )
            ->setPricePerNight(
                $updatedHouse->getPricePerNight() ??
                $existingHouse->getPricePerNight()
            )
            ->setHasAirConditioning(
                $updatedHouse->hasAirConditioning() ??
                $existingHouse->hasAirConditioning()
            )
            ->setHasWifi(
                $updatedHouse->hasWifi() ??
                $existingHouse->hasWifi()
            )
            ->setHasKitchen(
                $updatedHouse->hasKitchen() ??
                $existingHouse->hasKitchen()
            )
            ->setHasParking(
                $updatedHouse->hasParking() ??
                $existingHouse->hasParking()
            )
            ->setHasSeaView(
                $updatedHouse->hasSeaView() ??
                $existingHouse->hasSeaView()
            );

        if ($error = $this->validateHouse($existingHouse)) {
            return $error;
        }

        $this->housesRepository->updateHouse($existingHouse);
        return new JsonResponse(
            HousesMessages::updated(),
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'houses_delete', methods: ['DELETE'])]
    public function deleteHouse(int $id): JsonResponse
    {
        $house = $this->housesRepository->findHouseById($id);

        if (! $house) {
            return new JsonResponse(
                HousesMessages::notFound(),
                Response::HTTP_NOT_FOUND
            );
        }

        if (! $house->isAvailable()) {
            return new JsonResponse(
                HousesMessages::booked(),
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->housesRepository->deleteHouseById($id);
        return new JsonResponse(
            HousesMessages::deleted(),
            Response::HTTP_OK
        );
    }

    private function deserializeHouse(Request $request): House | JsonResponse
    {
        if ($request->getContentTypeFormat() !== 'json') {
            return new JsonResponse(
                HousesMessages::buildMessage(
                    'Deserialization failed',
                    ['Unsupported content type']
                ),
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        }

        try {
            return $this->serializer->deserialize(
                $request->getContent(),
                House::class,
                'json'
            );
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return new JsonResponse(
                HousesMessages::buildMessage(
                    'Deserialization failed',
                    [$e->getMessage()]
                ),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    private function validateHouse(House $house): ?JsonResponse
    {
        $errors = $this->validator->validate($house);
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
            HousesMessages::buildMessage(
                'Validation failed',
                $errorsArray
            ),
            Response::HTTP_BAD_REQUEST
        );
    }
}
