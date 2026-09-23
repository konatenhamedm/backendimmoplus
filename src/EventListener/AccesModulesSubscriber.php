<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\AccesModulesService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Refuse les appels API d'un grand module (Résidences, Terrains…) non inclus dans l'abonnement de l'entreprise.
 */
class AccesModulesSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private AccesModulesService $accesModules,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Après le pare-feu (priorité 8), pour connaître l'utilisateur connecté
        return [KernelEvents::REQUEST => ['onKernelRequest', 4]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $chemin = $event->getRequest()->getPathInfo();
        if (!str_starts_with($chemin, '/api/')) {
            return;
        }

        $user = $this->security->getUser();
        $module = $this->accesModules->getModuleInterdit($user instanceof User ? $user : null, $chemin);
        if (!$module) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'code' => 403,
            'message' => 'Validation failed',
            'errors' => ["Le module « {$module->getLibelle()} » n'est pas inclus dans votre abonnement."],
        ], 403));
    }
}
