<?php
namespace App\Controller;

use App\Entity\House;
use App\Repository\HousesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/houses', name: 'houses_api')]
class HousesController extends AbstractController
{
    private $housesRepository;
    private $validator;
    private $serializer;

    public function __construct(HousesRepository $housesRepository, ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->housesRepository = $housesRepository;
        $this->validator        = $validator;
        $this->serializer       = $serializer;
    }

    #[Route('/', name: 'houses_list', methods: ['GET'])]
    public function listHouses(): JsonResponse
    {
        return $this->json($this->housesRepository->findAllHouses());
    }

    #[Route('/', name: 'houses_add', methods: ['POST'])]
    public function addHouse(Request $request): JsonResponse
    {
        if ('json' !== $request->getContentTypeFormat()) {
            return $this->json(['status' => 'Unsupported content format'], 400);
        }

        try {
            $house = $this->serializer->deserialize($request->getContent(), House::class, 'json');
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return $this->json(['status' => 'Invalid JSON', 'error' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($house);
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

        $this->housesRepository->addHouse($house);

        return $this->json(['status' => 'House created!'], 201);
    }

    #[Route('/{id}', name: 'houses_get_by_id', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        $house = $this->housesRepository->findHouseById($id);
        if (! $house) {
            return $this->json(['status' => 'House not found'], 404);
        }

        return $this->json($house);
    }

    #[Route('/{id}', name: 'houses_replace_by_id', methods: ['PUT'])]
    public function replaceHouse(Request $request, int $id): JsonResponse
    {
        if ('json' !== $request->getContentTypeFormat()) {
            return $this->json(['status' => 'Unsupported content format'], 400);
        }

        $existingHouse = $this->housesRepository->findHouseById($id);
        if (! $existingHouse) {
            return $this->json(['status' => 'House not found'], 404);
        }

        try {
            $existingHouse = $this->serializer->deserialize($request->getContent(), House::class, 'json');
            $existingHouse->setId($id);
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return $this->json(['status' => 'Invalid JSON', 'error' => $e->getMessage()], 400);
        }

        $errors = $this->validator->validate($existingHouse);

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

        $this->housesRepository->updateHouse($existingHouse);

        return $this->json(['status' => 'House replaced!'], 200);
    }

    #[Route('/{id}', name: 'houses_update_by_id', methods: ['PATCH'])]
    public function updateHouse(Request $request, int $id): JsonResponse
    {
        if ('json' !== $request->getContentTypeFormat()) {
            return $this->json(['status' => 'Unsupported content format'], 400);
        }

        $existingHouse = $this->housesRepository->findHouseById($id);
        if (! $existingHouse) {
            return $this->json(['status' => 'House not found'], 404);
        }

        try {
            $updatedHouse = $this->serializer->deserialize($request->getContent(), House::class, 'json');
        } catch (NotEncodableValueException | UnexpectedValueException $e) {
            return $this->json(['status' => 'Invalid JSON', 'error' => $e->getMessage()], 400);
        }

        if ($updatedHouse->isAvailable() !== null) {
            $existingHouse->setIsAvailable($updatedHouse->isAvailable());
        }
        if ($updatedHouse->getBedroomsCount() !== null) {
            $existingHouse->setBedroomsCount($updatedHouse->getBedroomsCount());
        }
        if ($updatedHouse->getPricePerNight() !== null) {
            $existingHouse->setPricePerNight($updatedHouse->getPricePerNight());
        }
        if ($updatedHouse->hasAirConditioning() !== null) {
            $existingHouse->setHasAirConditioning($updatedHouse->hasAirConditioning());
        }
        if ($updatedHouse->hasWifi() !== null) {
            $existingHouse->setHasWifi($updatedHouse->hasWifi());
        }
        if ($updatedHouse->hasKitchen() !== null) {
            $existingHouse->setHasKitchen($updatedHouse->hasKitchen());
        }
        if ($updatedHouse->hasParking() !== null) {
            $existingHouse->setHasParking($updatedHouse->hasParking());
        }
        if ($updatedHouse->hasSeaView() !== null) {
            $existingHouse->setHasSeaView($updatedHouse->hasSeaView());
        }

        $errors = $this->validator->validate($existingHouse);

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

        $this->housesRepository->updateHouse($existingHouse);

        return $this->json(['status' => 'House updated!'], 200);
    }

    #[Route('/{id}', name: 'houses_delete', methods: ['DELETE'])]
    public function deleteHouse(Request $request, int $id): JsonResponse
    {
        if (! $this->housesRepository->findHouseById($id)) {
            return $this->json(['status' => 'House not found'], 404);
        }

        $this->housesRepository->deleteHouse($id);

        return $this->json(['status' => 'House deleted!'], 200);
    }
}
