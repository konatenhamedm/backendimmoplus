<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Repository\TypeVersementsRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/type-versements')]
#[OA\Tag(name: 'TypeVersements', description: 'Gestion des types de versements')]
class ApiTypeVersementsController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/type-versements/",
        summary: "Lister les types de versements",
        description: "Retourne la liste des types de versements.",
        tags: ['TypeVersements']
    )]
    public function index(Request $request, TypeVersementsRepository $repository): Response
    {
        try {
            $types = $repository->findAll();
            return $this->responseData($types, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
