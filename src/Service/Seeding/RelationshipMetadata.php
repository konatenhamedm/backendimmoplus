<?php

namespace App\Service\Seeding;

/**
 * Represents metadata for an entity relationship.
 * 
 * This class stores information about a relationship's type, target entity,
 * and constraints extracted from Doctrine entity annotations.
 */
class RelationshipMetadata
{
    /**
     * @param string $fieldName The name of the relationship field
     * @param string $type The relationship type (ManyToOne, OneToOne, OneToMany, ManyToMany)
     * @param string $targetEntity The fully qualified class name of the target entity
     * @param bool $nullable Whether the relationship can be null
     */
    public function __construct(
        private string $fieldName,
        private string $type,
        private string $targetEntity,
        private bool $nullable = false
    ) {
    }

    /**
     * Get the relationship field name.
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Get the relationship type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the target entity class name.
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    /**
     * Check if the relationship is nullable.
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
