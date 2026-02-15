<?php

namespace App\Service\Seeding;

/**
 * Represents metadata for a single entity field.
 * 
 * This class stores information about a field's type, constraints,
 * and validation rules extracted from Doctrine entity annotations.
 */
class FieldMetadata
{
    /**
     * @param string $name The field name
     * @param string $type The field type (string, integer, boolean, datetime, etc.)
     * @param int|null $length The maximum length for string fields, null for other types
     * @param bool $nullable Whether the field can be null
     * @param bool $unique Whether the field has a unique constraint
     * @param array $constraints Additional validation constraints
     */
    public function __construct(
        private string $name,
        private string $type,
        private ?int $length = null,
        private bool $nullable = false,
        private bool $unique = false,
        private array $constraints = []
    ) {
    }

    /**
     * Get the field name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the field type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the maximum length for string fields.
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * Check if the field is nullable.
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Check if the field has a unique constraint.
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Get additional validation constraints.
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
