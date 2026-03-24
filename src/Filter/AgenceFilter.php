<?php

namespace App\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class AgenceFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // On vérifie si l'entité exposant la propriété "agence"
        if ($targetEntity->hasAssociation('agence')) {
            // Retourne la condition SQL
            return sprintf('%s.agence_id = %s', $targetTableAlias, $this->getParameter('agence_id'));
        }

        return '';
    }
}
