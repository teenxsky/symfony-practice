<?php
namespace App\Controller;

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
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/houses', name: 'houses_api')]
class HousesController extends AbstractController
{
    private $houses_repository;
    private $serializer;
    private $validator;

    public function __construct(
        HousesRepository $houses_repository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->houses_repository = $houses_repository;
        $this->serializer        = $serializer;
        $this->validator         = $validator;
    }

    #[Route('/', name: 'houses_list', methods: ['GET'])]
    public function listHouses(): JsonResponse
    {
        return $this->json($this->houses_repository->findAllHouses());
    }

    #[Route('/', name: 'houses_add', methods: ['POST'])]
    public function addHouse(Request $request): JsonResponse
    {
        [
            'house' => $house,
            'error' => $err,
        ] = $this->houseSerialize($request);

        if ($err) {
            return $this->json(
                $err,
                Response::HTTP_BAD_REQUEST
            );
        }

        $err = $this->validateHouse($house);
        if (! empty($err)) {
            return $this->json(
                [
                    'status' => 'Validation failed',
                    'errors' => $err,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->houses_repository->addHouse($house);
        return $this->json(
            ['status' => 'House created!'],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'houses_get_by_id', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        $house = $this->houses_repository->findHouseById($id);
        if (! $house) {
            return $this->json(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json($house);
    }

    #[Route('/{id}', name: 'houses_replace_by_id', methods: ['PUT'])]
    public function replaceHouse(Request $request, int $id): JsonResponse
    {
        [
            'house' => $replacing_house,
            'error' => $err,
        ] = $this->houseSerialize($request);

        if ($err) {
            return $this->json(
                $err,
                Response::HTTP_BAD_REQUEST
            );
        }

        if (! $this->houses_repository->findHouseById($id)) {
            return $this->json(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }
        $replacing_house->setId($id);

        $err = $this->validateHouse($replacing_house);
        if (! empty($err)) {
            return $this->json(
                [
                    'status' => 'Validation failed',
                    'errors' => $err,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->houses_repository->updateHouse($replacing_house);
        return $this->json(
            ['status' => 'House replaced!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'houses_update_by_id', methods: ['PATCH'])]
    public function updateHouse(Request $request, int $id): JsonResponse
    {
        [
            'house' => $updated_house,
            'error' => $err,
        ] = $this->houseSerialize($request);

        if ($err) {
            return $this->json($err, Response::HTTP_BAD_REQUEST);
        }

        $existing_house = $this->houses_repository->findHouseById($id);
        if (! $existing_house) {
            return $this->json(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $existing_house
            ->setIsAvailable($updated_house->isAvailable() ?? $existing_house->isAvailable())
            ->setBedroomsCount($updated_house->getBedroomsCount() ?? $existing_house->getBedroomsCount())
            ->setPricePerNight($updated_house->getPricePerNight() ?? $existing_house->getPricePerNight())
            ->setHasAirConditioning($updated_house->hasAirConditioning() ?? $existing_house->hasAirConditioning())
            ->setHasWifi($updated_house->hasWifi() ?? $existing_house->hasWifi())
            ->setHasKitchen($updated_house->hasKitchen() ?? $existing_house->hasKitchen())
            ->setHasParking($updated_house->hasParking() ?? $existing_house->hasParking())
            ->setHasSeaView($updated_house->hasSeaView() ?? $existing_house->hasSeaView());

        $err = $this->validateHouse($existing_house);
        if (! empty($err)) {
            return $this->json(
                [
                    'status' => 'Validation failed',
                    'errors' => $err,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->houses_repository->updateHouse($existing_house);
        return $this->json(
            ['status' => 'House updated!'],
            Response::HTTP_OK
        );
    }

    #[Route('/{id}', name: 'houses_delete', methods: ['DELETE'])]
    public function deleteHouse(Request $request, int $id): JsonResponse
    {
        if (! $this->houses_repository->findHouseById($id)) {
            return $this->json(
                ['status' => 'House not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (! $this->houses_repository->findHouseById($id)->isAvailable()) {
            return $this->json(
                ['status' => 'House is booked'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->houses_repository->deleteHouse($id);
        return $this->json(
            ['status' => 'House deleted!'],
            Response::HTTP_OK
        );
    }

    private function houseSerialize(Request $request): array
    {
        if ($request->getContentTypeFormat() !== 'json') {
            return [
                'house' => null,
                'error' => [
                    'status' => 'Unsupported content format',
                ],
            ];
        }
        try {
            $house = $this->serializer->deserialize(
                $request->getContent(),
                House::class,
                'json'
            );
            return [
                'house' => $house,
                'error' => null,
            ];
        } catch (NotEncodableValueException | UnexpectedValueException $err) {
            return [
                'house' => null,
                'error' => [
                    'status' => 'Invalid JSON',
                    'error'  => $err->getMessage(),
                ],
            ];
        }
    }

    private function validateHouse(House $house): array
    {
        $errs = $this->validator->validate($house);
        if (count($errs) > 0) {
            $errs_array = [];
            foreach ($errs as $err) {
                $errs_array[] = [
                    'field'   => $err->getPropertyPath(),
                    'message' => $err->getMessage(),
                ];
            }
            return [
                'status' => 'Validation failed',
                'errors' => $errs_array,
            ];
        }
        return [];
    }
}
