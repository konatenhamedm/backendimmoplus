<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\SituationMatrimoniale;
use App\Repository\SituationMatrimonialeRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/situation-matrimoniale')]
#[OA\Tag(name: 'SituationMatrimoniale', description: 'Gestion des situations matrimoniales')]
class ApiSituationMatrimonialeController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/situation-matrimoniale/",
        summary: "Lister les situations matrimoniales",
        description: "Retourne la liste des situations matrimoniales.",
        tags: ['SituationMatrimoniale']
    )]
    public function index(Request $request, SituationMatrimonialeRepository $repository): Response
    {
        try {
            $situations = $repository->findAll();
            return $this->responseData($situations, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
