<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\Entreprise;
use App\Entity\Setting;
use App\Entity\User;
use App\Repository\AbonnementRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\SettingRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubscriptionChecker
{
    public function __construct(
        private UserRepository $userRepository,
        private SurccursaleRepository $surccursaleRepository

    ) {}

    public function getInactiveSubscriptions(Entreprise $entreprise)
    {
        return null;
    }

    public function checkInactiveSubscription(Entreprise $entreprise): array
    {
        $inactiveSubscriptions = $this->getInactiveSubscriptions($entreprise);

        if (empty($inactiveSubscriptions)) {
            return [",dnlkd,nd"];
        }

        // Formater les données pour la réponse
        /*  $formattedSubscriptions = array_map(function (Abonnement $abonnement) { */
        return [
            'id' => $inactiveSubscriptions->getId(),
            'type' => $inactiveSubscriptions->getType(),
            'dateFin' => $inactiveSubscriptions->getDateFin()->format('Y-m-d H:i:s'),
            'code' => $inactiveSubscriptions->getModuleAbonnement()?->getCode(),
            'daysSinceExpiration' => (new \DateTime())->diff($inactiveSubscriptions->getDateFin())->days
        ];
        /*  }, $inactiveSubscriptions); */

        return $formattedSubscriptions;
    }
}
