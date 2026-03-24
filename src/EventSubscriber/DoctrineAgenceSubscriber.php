<?php

namespace App\EventSubscriber;

use App\Entity\Agence;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: Events::prePersist)]
class DoctrineAgenceSubscriber
{
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(RequestStack $requestStack, Security $security)
    {
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $em = $args->getObjectManager();

        // Check if entity has setAgence
        if (method_exists($entity, 'setAgence') && method_exists($entity, 'getAgence')) {
            // Check if agence is not already set
            if (!$entity->getAgence()) {
                $request = $this->requestStack->getCurrentRequest();
                $agenceId = null;

                if ($request) {
                    $headerId = $request->headers->get('X-Agence-Id');
                    if ($headerId && $headerId !== 'null' && $headerId !== 'undefined') {
                        $agenceId = (int) $headerId;
                    }
                }

                // Fallback to user's agence
                if (!$agenceId && $this->security->getUser() && method_exists($this->security->getUser(), 'getAgence') && $this->security->getUser()->getAgence()) {
                    $agenceId = $this->security->getUser()->getAgence()->getId();
                }

                if ($agenceId) {
                    $agence = $em->getRepository(Agence::class)->find($agenceId);
                    if ($agence) {
                        $entity->setAgence($agence);
                    }
                }
            }
        }
    }
}
