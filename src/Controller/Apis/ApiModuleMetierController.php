<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Module;
use App\Entity\ModuleAbonnement;
use App\Entity\ModuleMetier;
use App\Repository\ModuleMetierRepository;
use App\Service\AccesModulesService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/module-metier')]
#[OA\Tag(name: 'ModuleMetier', description: "Grands modules vendus dans les abonnements (Loyers, Résidences, Terrains…)")]
class ApiModuleMetierController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/module-metier/",
        summary: "Lister les grands modules",
        description: "Avec les sections du menu rattachées et les formules qui les incluent.",
        tags: ['ModuleMetier']
    )]
    public function index(ModuleMetierRepository $repository): Response
    {
        try {
            $modules = $repository->findBy([], ['ordre' => 'ASC', 'libelle' => 'ASC']);

            return $this->response(array_map(fn (ModuleMetier $m) => $this->serialize($m), $modules));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/sections', methods: ['GET'])]
    #[OA\Get(
        path: "/api/module-metier/sections",
        summary: "Sections du menu et leur grand module",
        description: "Toutes les sections du menu ; module_metier_id vide = section commune, visible quel que soit l'abonnement.",
        tags: ['ModuleMetier']
    )]
    public function sections(): Response
    {
        try {
            $sections = $this->em->getRepository(Module::class)->findBy([], ['ordre' => 'ASC']);

            return $this->response(array_map(fn (Module $s) => [
                'id' => $s->getId(),
                'titre' => $s->getTitre(),
                'module_metier_id' => $s->getModuleMetier()?->getId(),
            ], $sections));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/mes-modules', methods: ['GET'])]
    #[OA\Get(
        path: "/api/module-metier/mes-modules",
        summary: "Grands modules accessibles à l'utilisateur connecté",
        description: "restreint = false : aucun filtrage (super administrateur, pas de formule ou aucun grand module paramétré).",
        tags: ['ModuleMetier']
    )]
    public function mesModules(AccesModulesService $accesModules): Response
    {
        try {
            $codes = $accesModules->getCodesAutorises($this->getUser());
            $modules = array_filter($accesModules->getModulesActifs(), fn (ModuleMetier $m) => $codes === null || in_array($m->getCode(), $codes, true));

            return $this->response([
                'restreint' => $codes !== null,
                'modules' => array_values(array_map(fn (ModuleMetier $m) => [
                    'code' => $m->getCode(),
                    'libelle' => $m->getLibelle(),
                    'icone' => $m->getIcone(),
                ], $modules)),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(path: "/api/module-metier/create", summary: "Créer un grand module", tags: ['ModuleMetier'])]
    public function create(Request $request, ModuleMetierRepository $repository): Response
    {
        try {
            if ($refus = $this->refuserSiPasSuperAdmin()) {
                return $refus;
            }
            $data = json_decode($request->getContent(), true) ?? [];
            if (trim($data['code'] ?? '') === '' || trim($data['libelle'] ?? '') === '') {
                return $this->errorResponse(null, "Le code et le libellé sont requis", 400);
            }
            if ($repository->findOneBy(['code' => strtoupper(trim($data['code']))])) {
                return $this->errorResponse(null, "Ce code est déjà utilisé", 400);
            }

            $module = (new ModuleMetier())->setCode($data['code']);
            $this->hydrate($module, $data);
            $this->updateAuditFields($module, true);
            $this->em->persist($module);
            $this->em->flush();

            return $this->response($this->serialize($module));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'], requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: "/api/module-metier/{id}",
        summary: "Modifier un grand module",
        description: "sections : IDs des sections du menu à rattacher (remplace la liste actuelle).",
        tags: ['ModuleMetier']
    )]
    public function update(int $id, Request $request, ModuleMetierRepository $repository): Response
    {
        try {
            if ($refus = $this->refuserSiPasSuperAdmin()) {
                return $refus;
            }
            $module = $repository->find($id);
            if (!$module) {
                return $this->errorResponse(null, "Grand module introuvable", 404);
            }

            $this->hydrate($module, json_decode($request->getContent(), true) ?? []);
            $this->updateAuditFields($module);
            $this->em->flush();

            return $this->response($this->serialize($module));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[OA\Delete(
        path: "/api/module-metier/{id}",
        summary: "Supprimer un grand module",
        description: "Ses sections du menu redeviennent communes et il est retiré des formules.",
        tags: ['ModuleMetier']
    )]
    public function delete(int $id, ModuleMetierRepository $repository): Response
    {
        try {
            if ($refus = $this->refuserSiPasSuperAdmin()) {
                return $refus;
            }
            $module = $repository->find($id);
            if (!$module) {
                return $this->errorResponse(null, "Grand module introuvable", 404);
            }

            foreach ($module->getSections() as $section) {
                $section->setModuleMetier(null);
            }
            foreach ($this->em->getRepository(ModuleAbonnement::class)->findAll() as $formule) {
                if ($formule->getModulesMetier()->contains($module)) {
                    $formule->setModulesMetier(array_filter($formule->getModulesMetier()->toArray(), fn ($m) => $m !== $module));
                }
            }
            $this->em->remove($module);
            $this->em->flush();

            return $this->response(['message' => 'Grand module supprimé']);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    private function refuserSiPasSuperAdmin(): ?Response
    {
        return $this->getUser()?->getGroupe()?->getCode() === 'SADM'
            ? null
            : $this->errorResponse(null, "Réservé au super administrateur", 403);
    }

    private function hydrate(ModuleMetier $module, array $data): void
    {
        if (trim($data['libelle'] ?? '') !== '') $module->setLibelle(trim($data['libelle']));
        if (array_key_exists('description', $data)) $module->setDescription($data['description'] ?: null);
        if (array_key_exists('icone', $data)) $module->setIcone($data['icone'] ?: null);
        if (isset($data['ordre'])) $module->setOrdre((int) $data['ordre']);
        if (array_key_exists('actif', $data)) $module->setIsActive((bool) $data['actif']);
        if (isset($data['prefixesApi']) && is_array($data['prefixesApi'])) $module->setPrefixesApi($data['prefixesApi']);

        if (isset($data['sections']) && is_array($data['sections'])) {
            $ids = array_map('intval', $data['sections']);
            foreach ($module->getSections()->toArray() as $section) {
                if (!in_array($section->getId(), $ids, true)) {
                    $section->setModuleMetier(null);
                    $module->getSections()->removeElement($section);
                }
            }
            foreach ($ids ? $this->em->getRepository(Module::class)->findBy(['id' => $ids]) : [] as $section) {
                // Une section n'appartient qu'à un seul grand module
                $section->getModuleMetier()?->getSections()->removeElement($section);
                $section->setModuleMetier($module);
                if (!$module->getSections()->contains($section)) {
                    $module->getSections()->add($section);
                }
            }
        }
    }

    private function serialize(ModuleMetier $m): array
    {
        $formules = $this->em->createQueryBuilder()
            ->select('f.id', 'f.code')
            ->from(ModuleAbonnement::class, 'f')
            ->join('f.modulesMetier', 'mm')
            ->where('mm = :module')
            ->setParameter('module', $m)
            ->getQuery()
            ->getArrayResult();

        return [
            'id' => $m->getId(),
            'code' => $m->getCode(),
            'libelle' => $m->getLibelle(),
            'description' => $m->getDescription(),
            'icone' => $m->getIcone(),
            'ordre' => $m->getOrdre(),
            'actif' => (bool) $m->isActive(),
            'prefixesApi' => $m->getPrefixesApi(),
            'sections' => $m->getSections()->map(fn (Module $s) => ['id' => $s->getId(), 'titre' => $s->getTitre()])->getValues(),
            'formules' => $formules,
        ];
    }
}
