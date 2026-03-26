<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\SuiviContact;
use App\Repository\LocataireRepository;
use App\Repository\SuiviContactRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/suivi-contact')]
#[OA\Tag(name: 'SuiviContact', description: 'Journal de suivi des contacts locataires')]
class ApiSuiviContactController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(summary: "Lister les contacts", tags: ['SuiviContact'])]
    public function index(SuiviContactRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) return $this->errorResponse(null, "Non authentifié", 401);

            if ($user->getEntreprise()) {
                $contacts = $repository->findAllByEntreprise($user->getEntreprise());
            } else {
                $contacts = $repository->findAll();
            }

            return $this->responseData($contacts, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(summary: "Créer un nouveau contact", tags: ['SuiviContact'])]
    public function create(Request $request, SuiviContactRepository $repository, LocataireRepository $locataireRepository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) return $this->errorResponse(null, "Non authentifié", 401);

            $data = json_decode($request->getContent(), true);
            $contact = new SuiviContact();

            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
                $contact->setLocataire($locataire);
            } else {
                 return $this->errorResponse(null, "locataire_id requis", 400);
            }

            $contact->setAgent($user);
            $contact->setEntreprise($user->getEntreprise());
            if (isset($data['typeContact'])) $contact->setTypeContact($data['typeContact']);
            if (isset($data['statusAction'])) $contact->setStatusAction($data['statusAction']);
            if (isset($data['description'])) $contact->setDescription($data['description']);
            if (isset($data['dateContact'])) $contact->setDateContact(new \DateTime($data['dateContact']));

            $repository->save($contact, true);

            return $this->responseData($contact, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(summary: "Supprimer un contact", tags: ['SuiviContact'])]
    public function delete(SuiviContact $contact, SuiviContactRepository $repository): Response
    {
        try {
            if (!$contact) return $this->errorResponse(null, "Contact non trouvé", 404);
            $repository->remove($contact, true);
            return $this->response(['message' => 'Contact supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
