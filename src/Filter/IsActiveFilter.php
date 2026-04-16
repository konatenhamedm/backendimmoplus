<?php

namespace App\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class IsActiveFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // On vérifie si l'entité possède le champ "isActive"
        if ($targetEntity->hasField('isActive')) {
            // Retourne la condition SQL pour ne récupérer que les éléments actifs
            return sprintf('%s.is_active = 1', $targetTableAlias);
        }

        return '';
    }
}
