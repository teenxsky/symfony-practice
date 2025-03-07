<?php
namespace App\Controller;

use App\Entity\House;
use App\Exception\DeserializeContentException;
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

#[Route('/api/v1/houses', name: 'houses_api')]
class HousesController extends AbstractController
{
    private HousesRepository $housesRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        HousesRepository $housesRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->housesRepository = $housesRepository;
        $this->serializer       = $serializer;
        $this->validator        = $validator;
    }

    #[Route('/', name: 'houses_list', methods: ['GET'])]
    public function listHouses(): JsonResponse
    {
        $housesArray = array_map(
            fn($house) => $house->toArray(),
            $this->housesRepository->findAllHouses()
        );

        return new JsonResponse(
            $housesArray,
            Response::HTTP_OK
        );
    }

    #[Route('/', name: 'houses_add', methods: ['POST'])]
    public function addHouse(Request $request): JsonResponse
    {
        try {
            $house = $this->houseDeserialize($request);
        } catch (DeserializeContentException $e) {
            return new JsonResponse(
                ['status' => $e->getMessage()],
                $e->getStatusCode()
            );
        }

        $errs = $this->validateHouse($house);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->housesRepository->addHouse($house);
        return new JsonResponse(
            ['status' => 'House created!'],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'houses_get_by_id', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        $house = $this->housesRepository->findHouseById($id);

        if (! $house) {
            return new JsonResponse(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($house->toArray(), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'houses_replace_by_id', methods: ['PUT'])]
    public function replaceHouse(Request $request, int $id): JsonResponse
    {
        try {
            $replacingHouse = $this->houseDeserialize($request);
        } catch (DeserializeContentException $e) {
            return new JsonResponse(
                ['status' => $e->getMessage()],
                $e->getStatusCode()
            );
        }

        if (! $this->housesRepository->findHouseById($id)) {
            return new JsonResponse(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $replacingHouse->setId($id);

        $errs = $this->validateHouse($replacingHouse);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->housesRepository->updateHouse($replacingHouse);
        return new JsonResponse(
            ['status' => 'House replaced!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'houses_update_by_id', methods: ['PATCH'])]
    public function updateHouse(Request $request, int $id): JsonResponse
    {
        try {
            $updatedHouse = $this->houseDeserialize($request);
        } catch (DeserializeContentException $e) {
            return new JsonResponse(
                ['status' => $e->getMessage()],
                $e->getStatusCode()
            );
        }

        $existingHouse = $this->housesRepository->findHouseById($id);
        if (! $existingHouse) {
            return new JsonResponse(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $existingHouse
            ->setIsAvailable($updatedHouse->isAvailable() ?? $existingHouse->isAvailable())
            ->setBedroomsCount($updatedHouse->getBedroomsCount() ?? $existingHouse->getBedroomsCount())
            ->setPricePerNight($updatedHouse->getPricePerNight() ?? $existingHouse->getPricePerNight())
            ->setHasAirConditioning($updatedHouse->hasAirConditioning() ?? $existingHouse->hasAirConditioning())
            ->setHasWifi($updatedHouse->hasWifi() ?? $existingHouse->hasWifi())
            ->setHasKitchen($updatedHouse->hasKitchen() ?? $existingHouse->hasKitchen())
            ->setHasParking($updatedHouse->hasParking() ?? $existingHouse->hasParking())
            ->setHasSeaView($updatedHouse->hasSeaView() ?? $existingHouse->hasSeaView());

        $errs = $this->validateHouse($existingHouse);
        if (! empty($errs)) {
            return new JsonResponse(
                [
                    'status' => 'Validation failed',
                    'errors' => $errs,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->housesRepository->updateHouse($existingHouse);
        return new JsonResponse(
            ['status' => 'House updated!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'houses_delete', methods: ['DELETE'])]
    public function deleteHouse(Request $request, int $id): JsonResponse
    {
        $house = $this->housesRepository->findHouseById($id);

        if (! $house) {
            return new JsonResponse(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (! $house->isAvailable()) {
            return new JsonResponse(
                ['status' => 'House is booked'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->housesRepository->deleteHouse($id);
        return new JsonResponse(
            ['status' => 'House deleted!'],
            Response::HTTP_OK
        );
    }

    private function houseDeserialize(Request $request): House
    {
        if ($request->getContentTypeFormat() !== 'json') {
            throw new DeserializeContentException();
        }

        try {
            return $this->serializer->deserialize(
                $request->getContent(),
                House::class,
                'json'
            );
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            throw new DeserializeContentException($e->getMessage());
        }
    }

    private function validateHouse(House $house): array
    {
        $errs = $this->validator->validate($house);
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
