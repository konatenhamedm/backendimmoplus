<?php

namespace App\EventSubscriber;

use App\Entity\Agence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class AgenceSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $agenceId = $request->headers->get('X-Agence-Id');
        
        // Also support agence selection fallback from connected user if applicable
        if (!$agenceId && $this->security->getUser() && method_exists($this->security->getUser(), 'getAgence') && $this->security->getUser()->getAgence()) {
            $agenceId = $this->security->getUser()->getAgence()->getId();
        }

        if ($agenceId && $agenceId !== 'null' && $agenceId !== 'undefined') {
            $filter = $this->entityManager->getFilters()->enable('agence_filter');
            $filter->setParameter('agence_id', (int) $agenceId);
            
            // Store the active agence ID in attributes so other listeners (e.g. Doctrine) can use it
            $request->attributes->set('active_agence_id', (int) $agenceId);
        }
    }
}
